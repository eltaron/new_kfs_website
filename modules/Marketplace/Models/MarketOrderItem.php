<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One line of an order, owned by a single vendor (vendor_id = sub-order key).
 */
class MarketOrderItem extends Model
{
    public const FULFILLMENT_PENDING = 'pending';
    public const FULFILLMENT_PROCESSING = 'processing';
    public const FULFILLMENT_SHIPPED = 'shipped';
    public const FULFILLMENT_DELIVERED = 'delivered';
    public const FULFILLMENT_CANCELLED = 'cancelled';

    public const FULFILLMENT_STATUSES = [
        self::FULFILLMENT_PENDING => 'جديد',
        self::FULFILLMENT_PROCESSING => 'قيد التجهيز',
        self::FULFILLMENT_SHIPPED => 'تم الشحن',
        self::FULFILLMENT_DELIVERED => 'تم التسليم',
        self::FULFILLMENT_CANCELLED => 'ملغي',
    ];

    protected $table = 'market_order_items';

    protected $fillable = [
        'order_id', 'vendor_id', 'product_id', 'product_name', 'variant', 'unit_price', 'quantity',
        'line_total', 'commission_rate', 'commission_amount', 'vendor_earning',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'vendor_earning' => 'decimal:2',
        'quantity' => 'integer',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketOrder::class, 'order_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(MarketVendor::class, 'vendor_id')->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketProduct::class, 'product_id')->withTrashed();
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(MarketPayout::class, 'payout_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(MarketReview::class, 'order_item_id');
    }

    public function fulfillmentLabel(): string
    {
        return self::FULFILLMENT_STATUSES[$this->fulfillment_status] ?? $this->fulfillment_status;
    }

    public function fulfillmentClass(): string
    {
        return match ($this->fulfillment_status) {
            self::FULFILLMENT_DELIVERED => 'status-success',
            self::FULFILLMENT_SHIPPED => 'status-info',
            self::FULFILLMENT_CANCELLED => 'status-danger',
            default => 'status-pending',
        };
    }
}
