<?php

namespace Modules\Marketplace\tests\Feature;

use Modules\Payments\Models\Transaction;
use Illuminate\Support\Facades\Notification;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketOrderItem;
use Modules\Marketplace\Models\MarketSetting;
use Modules\Marketplace\Notifications\MarketplaceNotification;
use Modules\Marketplace\Services\CheckoutService;
use Modules\Marketplace\Support\MarketSettings;
use Modules\Marketplace\tests\MarketplaceTestCase;

/**
 * Cart -> one order with per-vendor sub-orders, stock reservation, and the
 * Payable callbacks (markPaid / markPaymentFailed).
 */
class MarketplaceCheckoutTest extends MarketplaceTestCase
{
    /** @return array{0: MarketOrder, 1: \Modules\Marketplace\Models\MarketProduct, 2: \Modules\Marketplace\Models\MarketProduct} */
    private function placeTwoVendorOrder(): array
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $pA = $this->makeProduct($vendorA, ['price' => '150.50', 'stock' => 5]);
        $pB = $this->makeProduct($vendorB, ['price' => '40.00', 'stock' => 3]);
        $buyer = $this->makeUser();

        $this->actingAs($buyer);
        $this->post(route('market.cart.store'), ['product_id' => $pA->id, 'quantity' => 2])->assertSessionHasNoErrors();
        $this->post(route('market.cart.store'), ['product_id' => $pB->id, 'quantity' => 1])->assertSessionHasNoErrors();

        $response = $this->post(route('market.checkout.store'), $this->checkoutPayload());

        $order = MarketOrder::query()->where('user_id', $buyer->getKey())->latest('id')->firstOrFail();
        $response->assertRedirect(route('payments.pay', ['type' => 'market_order', 'id' => $order->id]));

        return [$order, $pA, $pB];
    }

    public function test_cart_with_two_vendors_becomes_one_order_split_by_vendor(): void
    {
        MarketSetting::query()->create(['key' => 'shipping_fee_per_vendor', 'value' => '25']);
        MarketSettings::forget();

        [$order, $pA, $pB] = $this->placeTwoVendorOrder();

        $this->assertSame(MarketOrder::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertSame(2, $order->items()->count());
        $this->assertCount(2, $order->subOrders());
        $this->assertEqualsCanonicalizing(
            [(int) $pA->vendor_id, (int) $pB->vendor_id],
            $order->items->pluck('vendor_id')->map(fn ($v) => (int) $v)->all(),
        );
        $this->assertSame((int) $pA->vendor_id, (int) $order->items->firstWhere('product_id', $pA->id)->vendor_id);

        $this->assertSame('341.00', (string) $order->subtotal);   // 2×150.50 + 40
        $this->assertSame('50.00', (string) $order->shipping_total); // 25 × 2 vendors
        $this->assertSame('391.00', (string) $order->total);
        $this->assertSame('391.00', $order->payableAmount());

        // 0% commission by default: the vendor keeps the whole line.
        $lineA = $order->items->firstWhere('product_id', $pA->id);
        $this->assertSame('301.00', (string) $lineA->vendor_earning);
        $this->assertSame('0.00', (string) $lineA->commission_amount);

        // Stock is held, not yet sold; the cart is emptied.
        $this->assertSame(2, $pA->fresh()->reserved_stock);
        $this->assertSame(5, $pA->fresh()->stock);
        $this->assertSame(1, $pB->fresh()->reserved_stock);
        $this->assertTrue($order->stock_reserved);
        $this->get(route('market.cart.show'))->assertSee('سلة المشتريات فارغة');
    }

    public function test_commission_is_applied_per_vendor(): void
    {
        $vendor = $this->makeVendor();
        $vendor->forceFill(['commission_rate' => '10.00'])->save();
        $product = $this->makeProduct($vendor, ['price' => '99.99', 'stock' => 3]);

        $this->actingAs($this->makeUser());
        $this->post(route('market.cart.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $this->post(route('market.checkout.store'), $this->checkoutPayload());

        $item = MarketOrderItem::query()->where('product_id', $product->id)->firstOrFail();
        $this->assertSame('10.00', (string) $item->commission_amount);
        $this->assertSame('89.99', (string) $item->vendor_earning);
    }

    public function test_mark_paid_decrements_stock_and_notifies_vendors(): void
    {
        Notification::fake();
        [$order, $pA, $pB] = $this->placeTwoVendorOrder();

        $order->refresh()->markPaid(new Transaction());

        $order->refresh();
        $this->assertSame(MarketOrder::STATUS_PAID, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertTrue($order->stock_committed);
        $this->assertFalse($order->stock_reserved);

        $pA->refresh();
        $pB->refresh();
        $this->assertSame(3, $pA->stock);
        $this->assertSame(0, $pA->reserved_stock);
        $this->assertSame(2, $pA->sales_count);
        $this->assertSame(2, $pB->stock);
        $this->assertSame(0, $pB->reserved_stock);

        Notification::assertSentTo($pA->vendor->user, MarketplaceNotification::class, fn ($n) => $n->kind === 'new_order');
        Notification::assertSentTo($pB->vendor->user, MarketplaceNotification::class, fn ($n) => $n->kind === 'new_order');
        Notification::assertSentTo($order->user, MarketplaceNotification::class, fn ($n) => $n->kind === 'order_confirmed');

        // A duplicate gateway callback must not decrement twice.
        $order->markPaid(new Transaction());
        $this->assertSame(3, $pA->fresh()->stock);
    }

    public function test_failed_payment_releases_the_reservation_and_can_be_retried(): void
    {
        [$order, $pA] = $this->placeTwoVendorOrder();

        $order->refresh()->markPaymentFailed(new Transaction());

        $order->refresh();
        $this->assertSame(MarketOrder::STATUS_PAYMENT_FAILED, $order->status);
        $this->assertFalse($order->stock_reserved);
        $this->assertSame(0, $pA->fresh()->reserved_stock);
        $this->assertSame(5, $pA->fresh()->stock);

        // Retry re-reserves.
        $this->post(route('market.orders.pay', $order))->assertRedirect(route('payments.pay', ['type' => 'market_order', 'id' => $order->id]));
        $this->assertSame(2, $pA->fresh()->reserved_stock);
        $this->assertSame(MarketOrder::STATUS_PENDING_PAYMENT, $order->fresh()->status);
    }

    public function test_cannot_check_out_more_than_available_stock(): void
    {
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor, ['stock' => 2]);
        $buyer = $this->makeUser();

        $this->actingAs($buyer);
        $this->post(route('market.cart.store'), ['product_id' => $product->id, 'quantity' => 1])->assertSessionHasNoErrors();

        // Another buyer's unpaid order now holds all remaining units.
        $product->forceFill(['reserved_stock' => 2])->save();

        $this->post(route('market.checkout.store'), $this->checkoutPayload())->assertSessionHasErrors();
        $this->assertFalse(MarketOrder::query()->where('user_id', $buyer->getKey())->exists());
        $this->assertSame(2, $product->fresh()->reserved_stock);
    }

    public function test_expired_reservations_are_released(): void
    {
        [$order, $pA] = $this->placeTwoVendorOrder();
        $order->forceFill(['reserved_until' => now()->subMinute()])->save();

        $released = app(CheckoutService::class)->releaseExpiredReservations();

        $this->assertGreaterThanOrEqual(1, $released);
        $this->assertSame(0, $pA->fresh()->reserved_stock);
        $this->assertSame(MarketOrder::STATUS_PAYMENT_FAILED, $order->fresh()->status);
    }

    public function test_cash_on_delivery_is_off_by_default(): void
    {
        $product = $this->makeProduct($this->makeVendor());
        $this->actingAs($this->makeUser());
        $this->post(route('market.cart.store'), ['product_id' => $product->id]);

        $this->post(route('market.checkout.store'), $this->checkoutPayload(['payment_method' => 'cod']))
            ->assertSessionHasErrors('payment_method');
    }

    public function test_cash_on_delivery_commits_stock_immediately_when_enabled(): void
    {
        MarketSettings::set('cod_enabled', '1');
        $product = $this->makeProduct($this->makeVendor(), ['stock' => 4]);
        $this->actingAs($this->makeUser());
        $this->post(route('market.cart.store'), ['product_id' => $product->id, 'quantity' => 3]);

        $this->post(route('market.checkout.store'), $this->checkoutPayload(['payment_method' => 'cod']))->assertRedirect();

        $order = MarketOrder::query()->where('user_id', auth()->id())->firstOrFail();
        $this->assertSame(MarketOrder::STATUS_PROCESSING, $order->status);
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame(0, $product->fresh()->reserved_stock);
    }
}
