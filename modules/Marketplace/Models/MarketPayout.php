<?php

namespace Modules\Marketplace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Payout ledger entry: money transferred from the governorate to a vendor
 * for delivered, e-Finance-paid items.
 */
class MarketPayout extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => 'قيد التحويل',
        self::STATUS_PAID => 'تم التحويل',
        self::STATUS_CANCELLED => 'ملغي',
    ];

    protected $table = 'market_payouts';

    protected $fillable = ['vendor_id', 'amount', 'status', 'method', 'reference', 'notes', 'created_by', 'paid_at'];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(MarketVendor::class, 'vendor_id')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketOrderItem::class, 'payout_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
