<?php

namespace Modules\Marketplace\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketOrderItem;

/**
 * Vendor-side fulfilment of their order items, rolling the parent order status up.
 */
class FulfillmentService
{
    /** Allowed forward transitions. */
    private const TRANSITIONS = [
        MarketOrderItem::FULFILLMENT_PENDING => [MarketOrderItem::FULFILLMENT_PROCESSING, MarketOrderItem::FULFILLMENT_SHIPPED, MarketOrderItem::FULFILLMENT_CANCELLED],
        MarketOrderItem::FULFILLMENT_PROCESSING => [MarketOrderItem::FULFILLMENT_SHIPPED, MarketOrderItem::FULFILLMENT_CANCELLED],
        MarketOrderItem::FULFILLMENT_SHIPPED => [MarketOrderItem::FULFILLMENT_DELIVERED],
        MarketOrderItem::FULFILLMENT_DELIVERED => [],
        MarketOrderItem::FULFILLMENT_CANCELLED => [],
    ];

    /** @return array<string, string> options valid from the item's current status */
    public static function nextOptions(MarketOrderItem $item): array
    {
        return collect(self::TRANSITIONS[$item->fulfillment_status] ?? [])
            ->mapWithKeys(fn ($s) => [$s => MarketOrderItem::FULFILLMENT_STATUSES[$s]])
            ->all();
    }

    public function __construct(
        private readonly OrderNotifier $notifier,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function update(MarketOrderItem $item, string $status, ?string $note = null): MarketOrderItem
    {
        return DB::transaction(function () use ($item, $status, $note) {
            /** @var MarketOrderItem $locked */
            $locked = MarketOrderItem::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            $order = MarketOrder::query()->whereKey($locked->order_id)->lockForUpdate()->firstOrFail();

            if (! $order->isFulfillable()) {
                throw ValidationException::withMessages(['status' => 'لا يمكن تجهيز طلب لم يتم تأكيده أو دفعه بعد.']);
            }
            if (! in_array($status, self::TRANSITIONS[$locked->fulfillment_status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'لا يمكن تغيير الحالة من «'.$locked->fulfillmentLabel().'» إلى الحالة المطلوبة.']);
            }

            $locked->fulfillment_status = $status;
            $locked->tracking_note = $note !== null && $note !== '' ? mb_substr($note, 0, 255) : $locked->tracking_note;
            if ($status === MarketOrderItem::FULFILLMENT_SHIPPED) {
                $locked->shipped_at = now();
            }
            if ($status === MarketOrderItem::FULFILLMENT_DELIVERED) {
                $locked->delivered_at = now();
            }
            $locked->save();

            if ($status === MarketOrderItem::FULFILLMENT_CANCELLED) {
                if ($locked->product_id && $order->stock_committed) {
                    // Item cancelled by vendor after stock was taken: put it back.
                    DB::table('market_products')->where('id', $locked->product_id)->update([
                        'stock' => DB::raw('stock + '.(int) $locked->quantity),
                    ]);
                }
                if ($order->paid_at !== null) {
                    // Buyer already paid online for this item: admins must arrange a refund.
                    $order->needs_review = true;
                    $order->save();
                }
            }

            $this->rollUp($order);
            $this->notifier->fulfillmentChanged($locked);

            return $locked;
        });
    }

    /** Derive the order status from its items. */
    public function rollUp(MarketOrder $order): void
    {
        $statuses = $order->items()->pluck('fulfillment_status');
        if ($statuses->isEmpty()) {
            return;
        }

        $active = $statuses->reject(fn ($s) => $s === MarketOrderItem::FULFILLMENT_CANCELLED);

        $new = match (true) {
            $active->isEmpty() => MarketOrder::STATUS_CANCELLED,
            $active->every(fn ($s) => $s === MarketOrderItem::FULFILLMENT_DELIVERED) => MarketOrder::STATUS_DELIVERED,
            $active->contains(fn ($s) => in_array($s, [MarketOrderItem::FULFILLMENT_SHIPPED, MarketOrderItem::FULFILLMENT_DELIVERED], true)) => MarketOrder::STATUS_SHIPPED,
            $active->contains(MarketOrderItem::FULFILLMENT_PROCESSING) => MarketOrder::STATUS_PROCESSING,
            default => $order->status,
        };

        if ($new !== $order->status) {
            $order->status = $new;
            if ($new === MarketOrder::STATUS_CANCELLED) {
                $order->cancelled_at = now();
            }
            $order->save();
        }
    }
}
