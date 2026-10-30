<?php

namespace Modules\Marketplace\tests\Feature;

use Modules\Payments\Models\Transaction;
use Modules\Marketplace\Filament\Vendor\Resources\OrderItemResource;
use Modules\Marketplace\Filament\Vendor\Resources\ProductResource as VendorProductResource;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketOrderItem;
use Modules\Marketplace\Models\MarketReview;
use Modules\Marketplace\Services\FulfillmentService;
use Modules\Marketplace\tests\MarketplaceTestCase;

/**
 * Vendors see only their own products / order lines; buyers only their own orders.
 */
class MarketplaceAuthorizationTest extends MarketplaceTestCase
{
    public function test_vendor_cannot_manage_another_vendors_product(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $productA = $this->makeProduct($vendorA);
        $productB = $this->makeProduct($vendorB);

        $this->actingAs($vendorA->user);

        $this->assertTrue($vendorA->user->can('update', $productA));
        $this->assertFalse($vendorA->user->can('update', $productB));
        $this->assertFalse($vendorA->user->can('delete', $productB));
        $this->assertFalse($vendorA->user->can('moderate', $productA), 'vendors cannot publish their own products');

        // Panel queries are scoped to the signed-in vendor.
        $ids = VendorProductResource::getEloquentQuery()->pluck('id')->all();
        $this->assertContains($productA->id, $ids);
        $this->assertNotContains($productB->id, $ids);

        $this->get(VendorProductResource::getUrl('edit', ['record' => $productB], panel: 'vendor'))->assertNotFound();
        $this->get(VendorProductResource::getUrl('edit', ['record' => $productA], panel: 'vendor'))->assertOk();
    }

    public function test_vendor_sees_only_their_own_lines_of_a_shared_order(): void
    {
        $vendorA = $this->makeVendor();
        $vendorB = $this->makeVendor();
        $pA = $this->makeProduct($vendorA);
        $pB = $this->makeProduct($vendorB);

        $this->actingAs($this->makeUser());
        $this->post(route('market.cart.store'), ['product_id' => $pA->id]);
        $this->post(route('market.cart.store'), ['product_id' => $pB->id]);
        $this->post(route('market.checkout.store'), $this->checkoutPayload());
        $order = MarketOrder::query()->where('user_id', auth()->id())->firstOrFail();
        $order->markPaid(new Transaction());

        $lineA = $order->items()->where('vendor_id', $vendorA->id)->firstOrFail();
        $lineB = $order->items()->where('vendor_id', $vendorB->id)->firstOrFail();

        $this->actingAs($vendorA->user);
        $visible = OrderItemResource::getEloquentQuery()->pluck('id')->all();
        $this->assertSame([$lineA->id], $visible);
        $this->assertTrue($vendorA->user->can('update', $lineA));
        $this->assertFalse($vendorA->user->can('update', $lineB));
        $this->get(OrderItemResource::getUrl('view', ['record' => $lineB], panel: 'vendor'))->assertNotFound();
        $this->get(OrderItemResource::getUrl('view', ['record' => $lineA], panel: 'vendor'))->assertOk();
    }

    public function test_unpaid_orders_are_hidden_from_vendors(): void
    {
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor);

        $this->actingAs($this->makeUser());
        $this->post(route('market.cart.store'), ['product_id' => $product->id]);
        $this->post(route('market.checkout.store'), $this->checkoutPayload());

        $this->actingAs($vendor->user);
        $this->assertSame(0, OrderItemResource::getEloquentQuery()->count());
    }

    public function test_buyer_cannot_see_or_pay_someone_elses_order(): void
    {
        $product = $this->makeProduct($this->makeVendor());
        $owner = $this->makeUser();
        $this->actingAs($owner);
        $this->post(route('market.cart.store'), ['product_id' => $product->id]);
        $this->post(route('market.checkout.store'), $this->checkoutPayload());
        $order = MarketOrder::query()->where('user_id', $owner->getKey())->firstOrFail();

        $this->get(route('market.orders.show', $order))->assertOk()->assertSee($order->number);

        $stranger = $this->makeUser();
        $this->actingAs($stranger);
        $this->get(route('market.orders.show', $order))->assertNotFound();
        $this->post(route('market.orders.pay', $order))->assertNotFound();
        $this->post(route('market.orders.cancel', $order))->assertNotFound();
        $this->get(route('market.orders.index'))->assertOk()->assertDontSee($order->number);
    }

    public function test_only_verified_buyers_can_review(): void
    {
        $product = $this->makeProduct($this->makeVendor());
        $buyer = $this->makeUser();
        $stranger = $this->makeUser();

        $this->actingAs($stranger)
            ->post(route('market.reviews.store', $product->slug), ['rating' => 5])
            ->assertForbidden();

        $this->actingAs($buyer);
        $this->post(route('market.cart.store'), ['product_id' => $product->id]);
        $this->post(route('market.checkout.store'), $this->checkoutPayload());
        $order = MarketOrder::query()->where('user_id', $buyer->getKey())->firstOrFail();

        // Unpaid order: still not a verified buyer.
        $this->post(route('market.reviews.store', $product->slug), ['rating' => 4])->assertForbidden();

        $order->markPaid(new Transaction());
        $this->post(route('market.reviews.store', $product->slug), ['rating' => 4, 'comment' => 'منتج ممتاز'])->assertRedirect();

        $this->assertSame(1, MarketReview::query()->where('product_id', $product->id)->count());
        $this->assertSame('4.00', (string) $product->fresh()->rating_avg);
    }

    public function test_fulfilment_follows_allowed_transitions_and_rolls_up(): void
    {
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor);
        $this->actingAs($this->makeUser());
        $this->post(route('market.cart.store'), ['product_id' => $product->id]);
        $this->post(route('market.checkout.store'), $this->checkoutPayload());
        $order = MarketOrder::query()->where('user_id', auth()->id())->firstOrFail();
        $item = $order->items()->firstOrFail();

        $service = app(FulfillmentService::class);

        // Not paid yet: vendor cannot start fulfilment.
        $this->assertThrows(fn () => $service->update($item, MarketOrderItem::FULFILLMENT_SHIPPED));

        $order->markPaid(new Transaction());
        $service->update($item, MarketOrderItem::FULFILLMENT_SHIPPED, 'شحنة رقم 123');
        $this->assertSame(MarketOrder::STATUS_SHIPPED, $order->fresh()->status);

        // Cannot jump backwards.
        $this->assertThrows(fn () => $service->update($item->fresh(), MarketOrderItem::FULFILLMENT_PROCESSING));

        $service->update($item->fresh(), MarketOrderItem::FULFILLMENT_DELIVERED);
        $this->assertSame(MarketOrder::STATUS_DELIVERED, $order->fresh()->status);
    }

    public function test_private_card_document_is_restricted(): void
    {
        $vendor = $this->makeVendor();

        $this->actingAs($this->makeUser())
            ->get(route('market.vendors.document', $vendor->id))
            ->assertForbidden();
    }
}
