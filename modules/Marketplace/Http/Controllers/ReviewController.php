<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\MarketOrderItem;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Models\MarketReview;

class ReviewController extends Controller
{
    public function store(Request $request, string $slug)
    {
        $product = MarketProduct::query()->visible()->where('slug', $slug)->firstOrFail();
        $userId = $request->user()->getKey();

        if (! StoreController::isVerifiedBuyer($userId, $product->getKey())) {
            abort(403, 'التقييم متاح فقط لمن اشترى هذا المنتج.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [], ['rating' => 'التقييم', 'comment' => 'التعليق']);

        $itemId = MarketOrderItem::query()->where('product_id', $product->getKey())
            ->whereHas('order', fn ($q) => $q->where('user_id', $userId))
            ->latest('id')->value('id');

        MarketReview::query()->updateOrCreate(
            ['product_id' => $product->getKey(), 'user_id' => $userId],
            [
                'order_item_id' => $itemId,
                'rating' => (int) $data['rating'],
                'comment' => isset($data['comment']) ? strip_tags($data['comment']) : null,
            ],
        );

        return redirect()->to(route('market.products.show', $product->slug).'#reviews')
            ->with('success', 'شكراً لك! تم حفظ تقييمك.');
    }
}
