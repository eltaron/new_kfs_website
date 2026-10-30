<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Models\MarketWishlist;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $items = MarketWishlist::query()
            ->where('user_id', $request->user()->getKey())
            ->whereHas('product', fn ($q) => $q->visible())
            ->with(['product.primaryImage', 'product.vendor:id,store_name,slug'])
            ->latest()
            ->paginate(12);

        return view('marketplace::store.wishlist', compact('items'));
    }

    public function toggle(Request $request, int $product)
    {
        $product = MarketProduct::query()->visible()->findOrFail($product);
        $userId = $request->user()->getKey();

        $existing = MarketWishlist::query()->where('user_id', $userId)->where('product_id', $product->getKey())->first();
        if ($existing) {
            $existing->delete();
            $message = 'تمت إزالة «'.$product->name.'» من المفضلة.';
        } else {
            MarketWishlist::query()->create(['user_id' => $userId, 'product_id' => $product->getKey()]);
            $message = 'تمت إضافة «'.$product->name.'» إلى المفضلة.';
        }

        return back()->with('success', $message);
    }
}
