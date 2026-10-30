<?php

namespace Modules\Marketplace\Models;

use App\Services\Payments\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketCartItem extends Model
{
    protected $table = 'market_cart_items';

    protected $fillable = ['cart_id', 'product_id', 'variant', 'quantity'];

    protected $casts = ['quantity' => 'integer'];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(MarketCart::class, 'cart_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketProduct::class, 'product_id')->withTrashed();
    }

    public function lineTotal(): string
    {
        return Money::format(Money::cents($this->product?->price) * $this->quantity / 100);
    }
}
