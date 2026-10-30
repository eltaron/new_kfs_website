<?php

namespace Modules\Marketplace\tests\Feature;

use Modules\Payments\Models\Transaction;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketVendor;
use Modules\Marketplace\tests\MarketplaceTestCase;
use Spatie\Permission\Models\Role;

/**
 * Every page of /store-admin and /vendor renders for the right user.
 */
class MarketplacePanelsTest extends MarketplaceTestCase
{
    private function seedOrder(): array
    {
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor);
        $this->makeVendor(MarketVendor::STATUS_PENDING);

        $this->actingAs($this->makeUser());
        $this->post(route('market.cart.store'), ['product_id' => $product->id]);
        $this->post(route('market.checkout.store'), $this->checkoutPayload());
        $order = MarketOrder::query()->where('user_id', auth()->id())->firstOrFail();
        $order->markPaid(new Transaction());

        return [$vendor, $product, $order];
    }

    public function test_store_admin_pages_render(): void
    {
        [$vendor, $product, $order] = $this->seedOrder();
        $admin = $this->makeUser();
        $admin->assignRole(Role::findOrCreate('مشرف المتجر', 'web'));
        $this->actingAs($admin);

        foreach ([
            '/store-admin',
            '/store-admin/vendors',
            '/store-admin/vendors/'.$vendor->id,
            '/store-admin/vendors/'.$vendor->id.'/edit',
            '/store-admin/products',
            '/store-admin/products/'.$product->id.'/edit',
            '/store-admin/categories',
            '/store-admin/orders',
            '/store-admin/orders/'.$order->number,
            '/store-admin/payouts',
            '/store-admin/reviews',
            '/store-admin/reports',
            '/store-admin/settings',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_vendor_pages_render(): void
    {
        [$vendor, $product, $order] = $this->seedOrder();
        $item = $order->items()->firstOrFail();
        $this->actingAs($vendor->user);

        foreach ([
            '/vendor',
            '/vendor/products',
            '/vendor/products/create',
            '/vendor/products/'.$product->id.'/edit',
            '/vendor/orders',
            '/vendor/orders/'.$item->id,
            '/vendor/payouts',
            '/vendor/store-profile',
        ] as $url) {
            $this->get($url)->assertOk();
        }

        // Vendors never reach the admin panel.
        $this->get('/store-admin')->assertForbidden();
    }
}
