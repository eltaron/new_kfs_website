<?php

namespace Modules\Marketplace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketReview extends Model
{
    protected $table = 'market_reviews';

    protected $fillable = ['product_id', 'user_id', 'order_item_id', 'rating', 'comment', 'is_visible'];

    protected $casts = [
        'rating' => 'integer',
        'is_visible' => 'boolean',
    ];

    protected static function booted(): void
    {
        $refresh = fn (MarketReview $review) => static::refreshProductRating($review->product_id);
        static::saved($refresh);
        static::deleted($refresh);
    }

    public static function refreshProductRating(int $productId): void
    {
        $stats = static::query()
            ->where('product_id', $productId)
            ->where('is_visible', true)
            ->selectRaw('COUNT(*) as c, COALESCE(AVG(rating), 0) as a')
            ->first();

        MarketProduct::withTrashed()->whereKey($productId)->update([
            'rating_count' => (int) $stats->c,
            'rating_avg' => round((float) $stats->a, 2),
        ]);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketProduct::class, 'product_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(MarketOrderItem::class, 'order_item_id');
    }
}
