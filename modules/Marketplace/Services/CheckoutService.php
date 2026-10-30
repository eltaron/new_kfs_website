<?php

namespace Modules\Marketplace\Services;

use App\Models\User;
use App\Services\Payments\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Marketplace\Models\MarketCart;
use Modules\Marketplace\Models\MarketCartItem;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Support\MarketSettings;

/**
 * Cart -> one MarketOrder whose items carry their vendor_id (per-vendor sub-orders).
 * Prices and commission are always computed server-side.
 */
class CheckoutService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly CartService $carts,
        private readonly OrderNotifier $notifier,
    ) {
    }

    /**
     * @param  array{shipping_name:string, shipping_phone:string, shipping_center:string, shipping_address:string, notes?:string|null}  $shipping
     *
     * @throws ValidationException
     */
    public function placeOrder(User $buyer, MarketCart $cart, array $shipping, string $method = MarketOrder::METHOD_EFINANCE): MarketOrder
    {
        if ($method === MarketOrder::METHOD_COD && ! MarketSettings::codEnabled()) {
            throw ValidationException::withMessages(['payment_method' => 'الدفع عند الاستلام غير متاح حالياً.']);
        }
        if (! in_array($method, [MarketOrder::METHOD_EFINANCE, MarketOrder::METHOD_COD], true)) {
            throw ValidationException::withMessages(['payment_method' => 'طريقة الدفع غير صحيحة.']);
        }

        try {
            return DB::transaction(function () use ($buyer, $cart, $shipping, $method) {
                $lines = $cart->items()->with('product.vendor')->lockForUpdate()->get();
                if ($lines->isEmpty()) {
                    throw ValidationException::withMessages(['cart' => 'سلة المشتريات فارغة.']);
                }

                $order = new MarketOrder([
                    'user_id' => $buyer->getKey(),
                    'status' => MarketOrder::STATUS_PENDING_PAYMENT,
                    'payment_method' => $method,
                    'shipping_name' => $shipping['shipping_name'],
                    'shipping_phone' => $shipping['shipping_phone'],
                    'shipping_center' => $shipping['shipping_center'],
                    'shipping_address' => $shipping['shipping_address'],
                    'notes' => $shipping['notes'] ?? null,
                ]);
                $order->save();

                $subtotal = 0;
                $commission = 0;
                $vendors = [];

                /** @var MarketCartItem $line */
                foreach ($lines as $line) {
                    /** @var MarketProduct|null $product */
                    $product = $line->product;
                    if (! $product || ! $product->isPurchasable()) {
                        throw ValidationException::withMessages(['cart' => 'المنتج «'.($product?->name ?? '').'» لم يعد متاحاً. برجاء مراجعة السلة.']);
                    }
                    if ($product->vendor->user_id === $buyer->getKey()) {
                        throw ValidationException::withMessages(['cart' => 'لا يمكنك شراء منتجات متجرك.']);
                    }

                    $qty = max(1, (int) $line->quantity);
                    $unit = Money::cents($product->price);
                    $lineTotal = $unit * $qty;
                    $rate = (float) $product->vendor->effectiveCommissionRate();
                    $lineCommission = (int) round($lineTotal * $rate / 100);

                    $order->items()->create([
                        'vendor_id' => $product->vendor_id,
                        'product_id' => $product->getKey(),
                        'product_name' => $product->name,
                        'variant' => (string) $line->variant,
                        'unit_price' => Money::format($unit / 100),
                        'quantity' => $qty,
                        'line_total' => Money::format($lineTotal / 100),
                        'commission_rate' => number_format($rate, 2, '.', ''),
                        'commission_amount' => Money::format($lineCommission / 100),
                        'vendor_earning' => Money::format(($lineTotal - $lineCommission) / 100),
                    ]);

                    $subtotal += $lineTotal;
                    $commission += $lineCommission;
                    $vendors[$product->vendor_id] = true;
                }

                $shippingTotal = Money::cents(MarketSettings::shippingFeePerVendor()) * count($vendors);

                $order->subtotal = Money::format($subtotal / 100);
                $order->shipping_total = Money::format($shippingTotal / 100);
                $order->commission_total = Money::format($commission / 100);
                $order->total = Money::format(($subtotal + $shippingTotal) / 100);
                $order->load('items');

                if ($method === MarketOrder::METHOD_COD) {
                    $this->stock->commit($order, strict: true);
                    $order->status = MarketOrder::STATUS_PROCESSING;
                } else {
                    $this->stock->reserve($order);
                }
                $order->save();

                $this->carts->clear($cart);

                if ($method === MarketOrder::METHOD_COD) {
                    $this->notifier->orderConfirmed($order);
                }

                return $order;
            });
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages(['cart' => $e->getMessage()]);
        }
    }

    /**
     * Retry payment for an order whose previous attempt failed or whose hold
     * expired: re-reserve stock and reopen it for payment.
     *
     * @throws ValidationException
     */
    public function reopenForPayment(MarketOrder $order): MarketOrder
    {
        try {
            return DB::transaction(function () use ($order) {
                /** @var MarketOrder $locked */
                $locked = MarketOrder::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
                $locked->load('items');

                if ($locked->payment_method !== MarketOrder::METHOD_EFINANCE || $locked->isPaid()) {
                    throw ValidationException::withMessages(['order' => 'لا يمكن دفع هذا الطلب.']);
                }

                $expired = $locked->status === MarketOrder::STATUS_PENDING_PAYMENT
                    && $locked->reserved_until !== null && $locked->reserved_until->isPast();
                if ($expired) {
                    $this->stock->release($locked);
                }

                if (! in_array($locked->status, [MarketOrder::STATUS_PENDING_PAYMENT, MarketOrder::STATUS_PAYMENT_FAILED], true)) {
                    throw ValidationException::withMessages(['order' => 'لا يمكن دفع هذا الطلب في حالته الحالية.']);
                }

                if (! $locked->stock_reserved) {
                    $this->stock->reserve($locked);
                } else {
                    $locked->reserved_until = now()->addMinutes(max(1, (int) config('marketplace.reservation_minutes', 60)));
                }
                $locked->status = MarketOrder::STATUS_PENDING_PAYMENT;
                $locked->save();

                return $locked;
            });
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages(['order' => $e->getMessage()]);
        }
    }

    /** Buyer cancels an unpaid order: release the hold. */
    public function cancelUnpaid(MarketOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $locked = MarketOrder::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->isPaid() || ! in_array($locked->status, [MarketOrder::STATUS_PENDING_PAYMENT, MarketOrder::STATUS_PAYMENT_FAILED], true)) {
                throw ValidationException::withMessages(['order' => 'لا يمكن إلغاء هذا الطلب.']);
            }
            $locked->load('items');
            $this->stock->release($locked);
            $locked->status = MarketOrder::STATUS_CANCELLED;
            $locked->cancelled_at = now();
            $locked->reserved_until = null;
            $locked->save();
            $locked->items()->update(['fulfillment_status' => 'cancelled']);
        });
    }

    /** Release holds of e-Finance orders that were never paid in time. Returns count released. */
    public function releaseExpiredReservations(): int
    {
        $count = 0;
        MarketOrder::query()
            ->where('status', MarketOrder::STATUS_PENDING_PAYMENT)
            ->where('stock_reserved', true)
            ->where('reserved_until', '<', now())
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$count) {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order, &$count) {
                        $locked = MarketOrder::query()->whereKey($order->getKey())->lockForUpdate()->first();
                        if (! $locked || $locked->status !== MarketOrder::STATUS_PENDING_PAYMENT || ! $locked->stock_reserved || $locked->isPaid()) {
                            return;
                        }
                        $locked->load('items');
                        $this->stock->release($locked);
                        $locked->status = MarketOrder::STATUS_PAYMENT_FAILED;
                        $locked->reserved_until = null;
                        $locked->save();
                        $count++;
                    });
                }
            });

        return $count;
    }
}
