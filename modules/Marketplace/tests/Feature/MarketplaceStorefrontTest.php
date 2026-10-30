<?php

namespace Modules\Marketplace\tests\Feature;

use Illuminate\Validation\ValidationException;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Models\MarketProductImage;
use Modules\Marketplace\Models\MarketWishlist;
use Modules\Marketplace\tests\MarketplaceTestCase;

/**
 * Public pages render, search works, accessibility rules hold.
 */
class MarketplaceStorefrontTest extends MarketplaceTestCase
{
    public function test_public_pages_render(): void
    {
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor, ['name' => 'سلة خوص اختبارية', 'variants' => [['name' => 'اللون', 'options' => ['أزرق', 'أحمر']]]]);
        $product->images()->create(['path' => 'marketplace/demo/x.svg', 'alt_text' => 'سلة خوص بلون القش']);

        $this->get(route('market.home'))->assertOk()->assertSee('متجر ذوي الهمم')->assertSee('تخطَّ إلى محتوى المتجر');
        $this->get(route('market.products.index', ['q' => 'خوص اختبارية']))->assertOk()->assertSee('سلة خوص اختبارية');
        $this->get(route('market.categories.show', $product->category->slug))->assertOk()->assertSee($product->name);
        $this->get(route('market.products.show', $product->slug))->assertOk()
            ->assertSee('alt="سلة خوص بلون القش"', false)
            ->assertSee('اللون');
        $this->get(route('market.vendors.show', $vendor->slug))->assertOk()->assertSee($vendor->store_name);
        $this->get(route('market.cart.show'))->assertOk();
        $this->get(route('market.sell'))->assertOk();
    }

    public function test_draft_products_are_not_public(): void
    {
        $product = $this->makeProduct($this->makeVendor(), ['status' => MarketProduct::STATUS_DRAFT]);

        $this->get(route('market.products.show', $product->slug))->assertNotFound();
        $this->get(route('market.products.index'))->assertDontSee($product->name);
    }

    public function test_variant_must_be_chosen_from_the_list(): void
    {
        $product = $this->makeProduct($this->makeVendor(), ['variants' => [['name' => 'المقاس', 'options' => ['S', 'M']]]]);

        $this->post(route('market.cart.store'), ['product_id' => $product->id, 'variant' => ['XL']])
            ->assertSessionHasErrors('variant');
        $this->post(route('market.cart.store'), ['product_id' => $product->id, 'variant' => ['M']])
            ->assertSessionHasNoErrors();
    }

    public function test_product_image_requires_alt_text(): void
    {
        $product = $this->makeProduct($this->makeVendor());

        $this->expectException(ValidationException::class);
        MarketProductImage::query()->create(['product_id' => $product->id, 'path' => 'a.jpg', 'alt_text' => ' ']);
    }

    public function test_logged_in_pages_and_wishlist(): void
    {
        $product = $this->makeProduct($this->makeVendor());
        $user = $this->makeUser();

        $this->get(route('market.wishlist.index'))->assertRedirect(route('login'));

        $this->actingAs($user);
        $this->post(route('market.wishlist.toggle', $product->id))->assertRedirect();
        $this->assertTrue(MarketWishlist::query()->where('user_id', $user->getKey())->where('product_id', $product->id)->exists());
        $this->get(route('market.wishlist.index'))->assertOk()->assertSee($product->name);
        $this->get(route('market.orders.index'))->assertOk();
        $this->get(route('market.sell.apply'))->assertOk();

        $this->post(route('market.cart.store'), ['product_id' => $product->id]);
        $this->get(route('market.checkout'))->assertOk()->assertSee('عنوان التوصيل');
    }

    public function test_vendor_cannot_buy_from_own_store(): void
    {
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor);

        $this->actingAs($vendor->user)
            ->post(route('market.cart.store'), ['product_id' => $product->id])
            ->assertSessionHasErrors('quantity');
    }
}
