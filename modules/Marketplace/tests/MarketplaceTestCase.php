<?php

namespace Modules\Marketplace\tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\Marketplace\Models\MarketCategory;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Models\MarketVendor;
use Modules\Marketplace\Support\MarketSettings;
use Tests\TestCase;

/**
 * Shared fixtures for the marketplace tests (kfs_testing, rolled back per test).
 */
abstract class MarketplaceTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        MarketSettings::forget();
        config([
            'marketplace.default_commission_rate' => 0,
            'marketplace.shipping_fee_per_vendor' => 0,
            'marketplace.cod_enabled' => false,
        ]);
        // Runtime settings rows from other runs must not leak into assertions.
        \Modules\Marketplace\Models\MarketSetting::query()->delete();
        MarketSettings::forget();
    }

    protected function makeUser(array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'name' => 'مستخدم اختبار '.Str::random(4),
            'email' => 'mk-'.Str::lower(Str::random(12)).'@test.invalid',
            'password' => bcrypt('password'),
            'status' => 'approved',
        ], $attributes));
    }

    protected function makeVendor(string $status = MarketVendor::STATUS_APPROVED, ?User $user = null): MarketVendor
    {
        $user ??= $this->makeUser();
        $vendor = new MarketVendor([
            'user_id' => $user->getKey(),
            'store_name' => 'متجر اختبار '.Str::random(5),
            'disability_card_number' => 'T-'.Str::random(6),
            'national_id' => '29901011234567',
            'phone' => '01000000000',
            'center' => 'دسوق',
            'payout_method' => 'wallet',
            'payout_details' => ['wallet_number' => '01000000000'],
        ]);
        $vendor->forceFill(['status' => $status])->save();

        return $vendor;
    }

    protected function makeCategory(): MarketCategory
    {
        return MarketCategory::query()->create(['name' => 'قسم اختبار '.Str::random(5), 'is_active' => true]);
    }

    protected function makeProduct(MarketVendor $vendor, array $attributes = []): MarketProduct
    {
        $product = new MarketProduct(array_merge([
            'vendor_id' => $vendor->getKey(),
            'category_id' => $this->makeCategory()->getKey(),
            'name' => 'منتج اختبار '.Str::random(5),
            'description' => 'وصف منتج للاختبار',
            'price' => '100.00',
            'stock' => 10,
            'status' => MarketProduct::STATUS_PUBLISHED,
        ], $attributes));
        $product->save();

        return $product;
    }

    protected function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'shipping_name' => 'مشترٍ تجريبي',
            'shipping_phone' => '01012345678',
            'shipping_center' => 'دسوق',
            'shipping_address' => 'شارع الجمهورية، عمارة 5',
            'payment_method' => 'efinance',
        ], $overrides);
    }
}
