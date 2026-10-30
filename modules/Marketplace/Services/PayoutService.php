<?php

namespace Modules\Marketplace\Services;

use App\Models\User;
use App\Services\Payments\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketOrderItem;
use Modules\Marketplace\Models\MarketPayout;
use Modules\Marketplace\Models\MarketVendor;

/**
 * Vendor earnings & payout ledger.
 *
 * Only e-Finance paid, delivered items are settled through payouts. For
 * cash-on-delivery orders the vendor collects the money directly, so those
 * items are excluded from payouts (their commission, if any, is reported).
 */
class PayoutService
{
    public static function eligibleItems(int $vendorId): Builder
    {
        return MarketOrderItem::query()
            ->where('vendor_id', $vendorId)
            ->whereNull('payout_id')
            ->where('fulfillment_status', MarketOrderItem::FULFILLMENT_DELIVERED)
            ->whereHas('order', fn (Builder $q) => $q
                ->where('payment_method', MarketOrder::METHOD_EFINANCE)
                ->whereNotNull('paid_at'));
    }

    /** @return array{sales:string, commission:string, earnings:string, available:string, pending_payouts:string, paid_out:string, orders:int} */
    public static function summary(int $vendorId): array
    {
        $sold = MarketOrderItem::query()
            ->where('vendor_id', $vendorId)
            ->where('fulfillment_status', '!=', MarketOrderItem::FULFILLMENT_CANCELLED)
            ->whereHas('order', fn (Builder $q) => $q->whereIn('status', MarketOrder::FULFILLABLE));

        return [
            'sales' => Money::format((clone $sold)->sum('line_total')),
            'commission' => Money::format((clone $sold)->sum('commission_amount')),
            'earnings' => Money::format((clone $sold)->sum('vendor_earning')),
            'orders' => (int) (clone $sold)->distinct()->count('order_id'),
            'available' => Money::format(self::eligibleItems($vendorId)->sum('vendor_earning')),
            'pending_payouts' => Money::format(MarketPayout::query()->where('vendor_id', $vendorId)->where('status', MarketPayout::STATUS_PENDING)->sum('amount')),
            'paid_out' => Money::format(MarketPayout::query()->where('vendor_id', $vendorId)->where('status', MarketPayout::STATUS_PAID)->sum('amount')),
        ];
    }

    /**
     * Bundle every eligible item of the vendor into a new pending payout.
     *
     * @throws ValidationException
     */
    public function createFor(MarketVendor $vendor, ?User $by = null, ?string $notes = null): MarketPayout
    {
        return DB::transaction(function () use ($vendor, $by, $notes) {
            $items = self::eligibleItems($vendor->getKey())->lockForUpdate()->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['vendor_id' => 'لا توجد مستحقات متاحة للتحويل لهذا البائع.']);
            }

            $amount = $items->sum(fn (MarketOrderItem $i) => Money::cents($i->vendor_earning));

            $payout = MarketPayout::query()->create([
                'vendor_id' => $vendor->getKey(),
                'amount' => Money::format($amount / 100),
                'status' => MarketPayout::STATUS_PENDING,
                'method' => $vendor->payout_method,
                'notes' => $notes,
                'created_by' => $by?->getKey(),
            ]);

            MarketOrderItem::query()->whereIn('id', $items->modelKeys())->update(['payout_id' => $payout->getKey()]);

            return $payout;
        });
    }

    public function markPaid(MarketPayout $payout, ?string $reference): void
    {
        $payout->forceFill([
            'status' => MarketPayout::STATUS_PAID,
            'reference' => $reference,
            'paid_at' => now(),
        ])->save();
    }

    /** Cancelling releases the items back to "available". */
    public function cancel(MarketPayout $payout): void
    {
        DB::transaction(function () use ($payout) {
            if ($payout->status === MarketPayout::STATUS_PAID) {
                throw ValidationException::withMessages(['status' => 'لا يمكن إلغاء تحويل تم تنفيذه.']);
            }
            $payout->items()->update(['payout_id' => null]);
            $payout->forceFill(['status' => MarketPayout::STATUS_CANCELLED])->save();
        });
    }
}
