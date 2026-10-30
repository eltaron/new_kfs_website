<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\MarketCategory;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketOrderItem;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Models\MarketReview;
use Modules\Marketplace\Models\MarketVendor;
use Modules\Marketplace\Models\MarketWishlist;

class StoreController extends Controller
{
    public const SORTS = [
        'latest' => 'الأحدث',
        'popular' => 'الأكثر مبيعاً',
        'rating' => 'الأعلى تقييماً',
        'price_asc' => 'السعر: من الأقل للأعلى',
        'price_desc' => 'السعر: من الأعلى للأقل',
    ];

    public function home()
    {
        $categories = MarketCategory::query()->active()->whereNull('parent_id')
            ->withCount(['products as visible_count' => fn (Builder $q) => $q->visible()])
            ->get();

        $featured = $this->baseQuery()->where('is_featured', true)->latest('published_at')->take(8)->get();
        $latest = $this->baseQuery()->latest('published_at')->take(8)->get();
        $vendors = MarketVendor::query()->approved()
            ->withCount(['products as visible_count' => fn (Builder $q) => $q->visible()])
            ->orderByDesc('visible_count')->take(6)->get();

        return view('marketplace::store.home', compact('categories', 'featured', 'latest', 'vendors'));
    }

    public function index(Request $request, ?MarketCategory $category = null)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:120'],
            'min' => ['nullable', 'numeric', 'min:0'],
            'max' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:'.implode(',', array_keys(self::SORTS))],
            'in_stock' => ['nullable', 'boolean'],
        ]);

        if (! $category && filled($filters['category'] ?? null)) {
            $category = MarketCategory::query()->active()->where('slug', $filters['category'])->first();
        }

        $query = $this->baseQuery();

        if ($category) {
            $ids = $category->children()->pluck('id')->push($category->getKey());
            $query->whereIn('category_id', $ids);
        }
        if (filled($filters['q'] ?? null)) {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $filters['q']).'%';
            $query->where(fn (Builder $q) => $q->where('name', 'like', $term)->orWhere('description', 'like', $term));
        }
        if (isset($filters['min'])) {
            $query->where('price', '>=', $filters['min']);
        }
        if (isset($filters['max'])) {
            $query->where('price', '<=', $filters['max']);
        }
        if (! empty($filters['in_stock'])) {
            $query->whereRaw('stock > reserved_stock');
        }

        match ($filters['sort'] ?? 'latest') {
            'popular' => $query->orderByDesc('sales_count'),
            'rating' => $query->orderByDesc('rating_avg')->orderByDesc('rating_count'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->latest('published_at'),
        };

        $products = $query->paginate((int) config('marketplace.per_page', 12))->withQueryString();
        $categories = MarketCategory::query()->active()->get();

        return view('marketplace::store.products', [
            'products' => $products,
            'categories' => $categories,
            'category' => $category,
            'filters' => $filters,
            'sorts' => self::SORTS,
        ]);
    }

    public function category(Request $request, string $slug)
    {
        $category = MarketCategory::query()->active()->where('slug', $slug)->firstOrFail();

        return $this->index($request, $category);
    }

    public function show(Request $request, string $slug)
    {
        $product = MarketProduct::query()->visible()->where('slug', $slug)
            ->with(['images', 'vendor', 'category'])
            ->firstOrFail();

        $reviews = MarketReview::query()->where('product_id', $product->getKey())->where('is_visible', true)
            ->with('user:id,name')->latest()->take(20)->get();

        $user = $request->user();
        $canReview = $user !== null && self::isVerifiedBuyer($user->getKey(), $product->getKey());
        $myReview = $user ? $reviews->firstWhere('user_id', $user->getKey()) ?? MarketReview::query()->where('product_id', $product->getKey())->where('user_id', $user->getKey())->first() : null;
        $inWishlist = $user !== null && MarketWishlist::query()->where('user_id', $user->getKey())->where('product_id', $product->getKey())->exists();

        $related = $this->baseQuery()
            ->where('market_products.id', '!=', $product->getKey())
            ->where(fn (Builder $q) => $q->where('category_id', $product->category_id)->orWhere('vendor_id', $product->vendor_id))
            ->inRandomOrder()->take(4)->get();

        return view('marketplace::store.product', compact('product', 'reviews', 'canReview', 'myReview', 'inWishlist', 'related'));
    }

    public function vendor(string $slug)
    {
        $vendor = MarketVendor::query()->approved()->where('slug', $slug)->firstOrFail();
        $products = $this->baseQuery()->where('vendor_id', $vendor->getKey())->latest('published_at')
            ->paginate((int) config('marketplace.per_page', 12));

        return view('marketplace::store.vendor', compact('vendor', 'products'));
    }

    /** "Verified buyer": bought the product in a paid order, or received it (COD delivered). */
    public static function isVerifiedBuyer(string $userId, int $productId): bool
    {
        return MarketOrderItem::query()
            ->where('product_id', $productId)
            ->where('fulfillment_status', '!=', MarketOrderItem::FULFILLMENT_CANCELLED)
            ->whereHas('order', fn (Builder $q) => $q->where('user_id', $userId))
            ->where(fn (Builder $q) => $q
                ->where('fulfillment_status', MarketOrderItem::FULFILLMENT_DELIVERED)
                ->orWhereHas('order', fn (Builder $o) => $o->whereNotNull('paid_at')->where('status', '!=', MarketOrder::STATUS_CANCELLED)))
            ->exists();
    }

    private function baseQuery(): Builder
    {
        return MarketProduct::query()->visible()->with(['primaryImage', 'vendor:id,store_name,slug']);
    }
}
