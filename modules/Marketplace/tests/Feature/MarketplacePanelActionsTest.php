<?php

namespace Modules\Marketplace\tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Marketplace\Filament\Admin\Pages\MarketSettingsPage;
use Modules\Marketplace\Filament\Admin\Resources\ProductResource\Pages\ListProducts as AdminListProducts;
use Modules\Marketplace\Filament\Admin\Resources\VendorResource\Pages\ListVendors;
use Modules\Marketplace\Filament\Vendor\Resources\ProductResource\Pages\CreateProduct;
use Modules\Marketplace\Filament\Vendor\Resources\ProductResource\Pages\EditProduct;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Models\MarketVendor;
use Modules\Marketplace\Notifications\MarketplaceNotification;
use Modules\Marketplace\Support\MarketSettings;
use Modules\Marketplace\tests\MarketplaceTestCase;
use Spatie\Permission\Models\Role;

class MarketplacePanelActionsTest extends MarketplaceTestCase
{
    public function test_vendor_creates_a_draft_owned_by_them(): void
    {
        $vendor = $this->makeVendor();
        $other = $this->makeVendor();
        $category = $this->makeCategory();
        $this->actingAs($vendor->user);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'وسادة مطرزة',
                'category_id' => $category->id,
                'status' => MarketProduct::STATUS_DRAFT,
                'price' => '75',
                'stock' => 3,
                'vendor_id' => $other->id, // must be ignored
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = MarketProduct::query()->where('name', 'وسادة مطرزة')->firstOrFail();
        $this->assertSame((int) $vendor->id, (int) $product->vendor_id);
        $this->assertSame(MarketProduct::STATUS_DRAFT, $product->status);
    }

    public function test_submitting_for_review_requires_an_image(): void
    {
        $vendor = $this->makeVendor();
        $this->actingAs($vendor->user);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'منتج بدون صورة',
                'category_id' => $this->makeCategory()->id,
                'status' => MarketProduct::STATUS_PENDING,
                'price' => '50',
                'stock' => 1,
            ])
            ->call('create')
            ->assertHasFormErrors(['images']);
    }

    public function test_vendor_cannot_publish_by_editing_status(): void
    {
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor, ['status' => MarketProduct::STATUS_DRAFT]);
        $product->images()->create(['path' => 'a.svg', 'alt_text' => 'صورة توضيحية']);
        $this->actingAs($vendor->user);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));

        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->fillForm(['status' => MarketProduct::STATUS_PUBLISHED])
            ->call('save');

        $this->assertNotSame(MarketProduct::STATUS_PUBLISHED, $product->fresh()->status);
    }

    public function test_admin_publishes_product_and_approves_vendor_from_tables(): void
    {
        Notification::fake();
        $admin = $this->makeUser();
        $admin->assignRole(Role::findOrCreate('مشرف المتجر', 'web'));
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor, ['status' => MarketProduct::STATUS_PENDING]);
        $pendingVendor = $this->makeVendor(MarketVendor::STATUS_PENDING);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('store-admin'));

        Livewire::test(AdminListProducts::class)->callTableAction('publish', $product);
        $this->assertSame(MarketProduct::STATUS_PUBLISHED, $product->fresh()->status);
        Notification::assertSentTo($vendor->user, MarketplaceNotification::class, fn ($n) => $n->kind === 'product_moderated');

        Livewire::test(ListVendors::class)->callTableAction('reject', $pendingVendor, ['notes' => 'الصورة غير واضحة']);
        $this->assertSame(MarketVendor::STATUS_REJECTED, $pendingVendor->fresh()->status);
        $this->assertSame('الصورة غير واضحة', $pendingVendor->fresh()->review_notes);
    }

    public function test_admin_changes_commission_setting(): void
    {
        $admin = $this->makeUser();
        $admin->assignRole(Role::findOrCreate('مشرف المتجر', 'web'));
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('store-admin'));

        Livewire::test(MarketSettingsPage::class)
            ->fillForm(['default_commission_rate' => '5', 'shipping_fee_per_vendor' => '20', 'cod_enabled' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(5.0, MarketSettings::commissionRate());
        $this->assertSame(20.0, MarketSettings::shippingFeePerVendor());
        $this->assertTrue(MarketSettings::codEnabled());
    }
}
