<?php

namespace Modules\Marketplace\Services;

use App\Models\User;
use App\Services\Payments\Money;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Marketplace\Models\MarketCart;
use Modules\Marketplace\Models\MarketCartItem;
use Modules\Marketplace\Models\MarketProduct;

/**
 * Cart stored in the database: per user when signed in, otherwise per guest
 * token kept in the session. A guest cart is merged into the user cart on
 * first access after login.
 */
class CartService
{
    public const SESSION_KEY = 'market_cart_token';

    public function current(?User $user, Session $session, bool $create = false): ?MarketCart
    {
        $token = $session->get(self::SESSION_KEY);
        $guestCart = $token ? MarketCart::query()->where('token', $token)->whereNull('user_id')->first() : null;

        if ($user) {
            $cart = MarketCart::query()->where('user_id', $user->getKey())->first();
            if (! $cart && ($create || $guestCart)) {
                $cart = MarketCart::query()->create(['user_id' => $user->getKey()]);
            }
            if ($cart && $guestCart) {
                $this->merge($guestCart, $cart);
                $session->forget(self::SESSION_KEY);
            }

            return $cart;
        }

        if (! $guestCart && $create) {
            $token = Str::random(40);
            $session->put(self::SESSION_KEY, $token);
            $guestCart = MarketCart::query()->create(['token' => $token]);
        }

        return $guestCart;
    }

    /**
     * @throws ValidationException
     */
    public function add(MarketCart $cart, MarketProduct $product, int $quantity, array $variantSelection = [], ?User $user = null): MarketCartItem
    {
        $product->loadMissing('vendor');

        if (! $product->isPurchasable()) {
            throw ValidationException::withMessages(['quantity' => 'هذا المنتج غير متاح للشراء حالياً.']);
        }
        if ($user && $product->vendor?->user_id === $user->getKey()) {
            throw ValidationException::withMessages(['quantity' => 'لا يمكنك شراء منتجات متجرك.']);
        }

        $variant = '';
        if ($product->variantGroups() !== []) {
            $variant = $product->normalizeVariant($variantSelection);
            if ($variant === null) {
                throw ValidationException::withMessages(['variant' => 'برجاء اختيار جميع خيارات المنتج.']);
            }
        }

        $item = $cart->items()->firstOrNew(['product_id' => $product->getKey(), 'variant' => $variant]);
        $item->quantity = $this->clampQuantity($product, (int) ($item->exists ? $item->quantity : 0) + max(1, $quantity));
        $item->save();
        $cart->touch();

        return $item;
    }

    public function update(MarketCartItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            $item->delete();

            return;
        }
        $item->quantity = $this->clampQuantity($item->product, $quantity);
        $item->save();
    }

    public function count(?MarketCart $cart): int
    {
        return $cart ? (int) $cart->items()->sum('quantity') : 0;
    }

    /**
     * Cart lines grouped per vendor with totals, dropping lines that are no
     * longer purchasable.
     *
     * @return array{groups: \Illuminate\Support\Collection, subtotal: string, shipping: string, total: string, issues: array<int,string>}
     */
    public function summary(?MarketCart $cart): array
    {
        $issues = [];
        $items = collect();
        if ($cart) {
            $items = $cart->items()->with(['product.vendor', 'product.primaryImage'])->get()
                ->filter(function (MarketCartItem $item) use (&$issues) {
                    $product = $item->product;
                    if (! $product || ! $product->isPurchasable()) {
                        $issues[] = 'المنتج «'.($product?->name ?? 'محذوف').'» لم يعد متاحاً وتمت إزالته من السلة.';
                        $item->delete();

                        return false;
                    }
                    if ($item->quantity > $product->availableStock()) {
                        $item->quantity = $product->availableStock();
                        $item->save();
                        $issues[] = "تم تعديل كمية «{$product->name}» إلى المتاح ({$item->quantity}).";
                    }

                    return true;
                });
        }

        $groups = $items->groupBy(fn (MarketCartItem $i) => $i->product->vendor_id);
        $subtotal = $items->sum(fn (MarketCartItem $i) => Money::cents($i->product->price) * $i->quantity);
        $shipping = Money::cents(\Modules\Marketplace\Support\MarketSettings::shippingFeePerVendor()) * $groups->count();

        return [
            'groups' => $groups,
            'subtotal' => Money::format($subtotal / 100),
            'shipping' => Money::format($shipping / 100),
            'total' => Money::format(($subtotal + $shipping) / 100),
            'issues' => $issues,
        ];
    }

    public function clear(MarketCart $cart): void
    {
        $cart->items()->delete();
    }

    private function merge(MarketCart $from, MarketCart $into): void
    {
        DB::transaction(function () use ($from, $into) {
            foreach ($from->items()->with('product')->get() as $item) {
                $existing = $into->items()->where('product_id', $item->product_id)->where('variant', $item->variant)->first();
                if ($existing) {
                    $existing->quantity = $this->clampQuantity($item->product, $existing->quantity + $item->quantity);
                    $existing->save();
                } else {
                    $into->items()->create($item->only(['product_id', 'variant', 'quantity']));
                }
            }
            $from->delete();
        });
    }

    private function clampQuantity(?MarketProduct $product, int $quantity): int
    {
        $max = min((int) config('marketplace.max_quantity_per_item', 20), $product?->availableStock() ?? 0);

        return max(1, min($quantity, max(1, $max)));
    }
}
