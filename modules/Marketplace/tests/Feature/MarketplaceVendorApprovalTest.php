<?php

namespace Modules\Marketplace\tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Modules\Marketplace\Filament\Admin\Resources\VendorResource;
use Modules\Marketplace\Models\MarketVendor;
use Modules\Marketplace\Notifications\MarketplaceNotification;
use Modules\Marketplace\tests\MarketplaceTestCase;
use Spatie\Permission\Models\Role;

/**
 * Vendor onboarding: application -> admin review -> access to /vendor.
 */
class MarketplaceVendorApprovalTest extends MarketplaceTestCase
{
    public function test_citizen_can_apply_and_application_is_pending(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = $this->makeUser(['national_id' => '29901011234599']);

        $this->actingAs($user)->post(route('market.sell.store'), [
            'store_name' => 'متجر الأمل',
            'description' => 'منتجات يدوية',
            'disability_card_number' => 'ISC-12345',
            'disability_card' => UploadedFile::fake()->create('card.pdf', 200, 'application/pdf'),
            'national_id' => '٢٩٩٠١٠١١٢٣٤٥٩٩', // Arabic digits are normalised
            'phone' => '01012345678',
            'center' => 'دسوق',
            'payout_method' => 'wallet',
            'wallet_number' => '01012345678',
            'terms' => '1',
        ])->assertRedirect(route('market.sell.status'));

        $vendor = MarketVendor::query()->where('user_id', $user->getKey())->firstOrFail();
        $this->assertSame(MarketVendor::STATUS_PENDING, $vendor->status);
        Storage::disk('local')->assertExists($vendor->disability_card_path);
        $this->assertSame(['wallet_number' => '01012345678'], $vendor->payout_details);
    }

    public function test_national_id_must_match_the_account(): void
    {
        Storage::fake('local');
        $user = $this->makeUser(['national_id' => '29901011234599']);

        $this->actingAs($user)->post(route('market.sell.store'), [
            'store_name' => 'متجر الأمل',
            'disability_card_number' => 'ISC-12345',
            'disability_card' => UploadedFile::fake()->create('card.pdf', 200, 'application/pdf'),
            'national_id' => '29901011111111',
            'phone' => '01012345678',
            'center' => 'دسوق',
            'payout_method' => 'wallet',
            'wallet_number' => '01012345678',
            'terms' => '1',
        ])->assertSessionHasErrors('national_id');

        $this->assertFalse(MarketVendor::query()->where('user_id', $user->getKey())->exists());
    }

    public function test_pending_vendor_cannot_enter_vendor_panel_or_sell(): void
    {
        $vendor = $this->makeVendor(MarketVendor::STATUS_PENDING);
        $product = $this->makeProduct($vendor);

        $this->actingAs($vendor->user)->get('/vendor')->assertRedirect(route('market.sell.status'));

        // Products of a non-approved vendor are invisible and unbuyable.
        $this->get(route('market.products.show', $product->slug))->assertNotFound();
        $this->post(route('market.cart.store'), ['product_id' => $product->id, 'quantity' => 1])->assertNotFound();
    }

    public function test_guest_is_sent_to_citizen_login(): void
    {
        $this->get('/vendor')->assertRedirect(route('login'));
        $this->get('/store-admin')->assertRedirect(route('login'));
    }

    public function test_admin_approval_opens_the_panel_and_notifies_vendor(): void
    {
        Notification::fake();
        $admin = $this->makeUser();
        $admin->assignRole(Role::findOrCreate('مشرف المتجر', 'web'));
        $vendor = $this->makeVendor(MarketVendor::STATUS_PENDING);

        $this->actingAs($admin);
        VendorResource::changeStatus($vendor, MarketVendor::STATUS_APPROVED, null);

        $vendor->refresh();
        $this->assertSame(MarketVendor::STATUS_APPROVED, $vendor->status);
        $this->assertSame($admin->getKey(), $vendor->reviewed_by);
        Notification::assertSentTo($vendor->user, MarketplaceNotification::class, fn ($n) => $n->kind === 'vendor_status');

        $this->actingAs($vendor->user)->get('/vendor')->assertOk();
    }

    public function test_suspending_a_vendor_hides_their_products(): void
    {
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor);
        $this->get(route('market.products.show', $product->slug))->assertOk();

        $vendor->forceFill(['status' => MarketVendor::STATUS_SUSPENDED])->save();

        $this->get(route('market.products.show', $product->slug))->assertNotFound();
        $this->get(route('market.vendors.show', $vendor->slug))->assertNotFound();
    }

    public function test_non_admin_cannot_review_or_enter_store_admin(): void
    {
        $user = $this->makeUser();
        $vendor = $this->makeVendor(MarketVendor::STATUS_PENDING);

        $this->actingAs($user)->get('/store-admin')->assertForbidden();
        $this->assertFalse($user->can('review', $vendor));
    }
}
