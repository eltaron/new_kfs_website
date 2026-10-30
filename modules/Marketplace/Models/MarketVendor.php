<?php

namespace Modules\Marketplace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Modules\Marketplace\Support\MarketSettings;
use Modules\Marketplace\Support\Slug;

/**
 * A citizen with a disability who sells through the marketplace.
 *
 * @property int $id
 * @property string $user_id
 * @property string $store_name
 * @property string $slug
 * @property string $status pending|approved|rejected|suspended
 * @property string|null $commission_rate
 */
class MarketVendor extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_PENDING => 'قيد المراجعة',
        self::STATUS_APPROVED => 'معتمد',
        self::STATUS_REJECTED => 'مرفوض',
        self::STATUS_SUSPENDED => 'موقوف',
    ];

    public const PAYOUT_METHODS = [
        'wallet' => 'محفظة إلكترونية',
        'bank' => 'حساب بنكي',
    ];

    protected $table = 'market_vendors';

    protected $fillable = [
        'user_id', 'store_name', 'slug', 'description', 'logo',
        'disability_card_number', 'disability_card_path', 'national_id', 'phone',
        'center', 'address', 'payout_method', 'payout_details',
    ];

    protected $hidden = ['payout_details', 'disability_card_path', 'national_id', 'disability_card_number'];

    protected $casts = [
        'payout_details' => 'encrypted:array',
        'reviewed_at' => 'datetime',
        'commission_rate' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (MarketVendor $vendor) {
            if (blank($vendor->slug) || $vendor->isDirty('slug')) {
                $vendor->slug = Slug::unique(static::class, $vendor->slug ?: $vendor->store_name, $vendor->getKey(), 'store');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function products(): HasMany
    {
        return $this->hasMany(MarketProduct::class, 'vendor_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(MarketOrderItem::class, 'vendor_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(MarketPayout::class, 'vendor_id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Design-system status class for badges. */
    public function statusClass(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'status-success',
            self::STATUS_PENDING => 'status-pending',
            default => 'status-danger',
        };
    }

    /** Commission percent applied to this vendor's sales. */
    public function effectiveCommissionRate(): string
    {
        return number_format((float) ($this->commission_rate ?? MarketSettings::commissionRate()), 2, '.', '');
    }

    public function logoUrl(): ?string
    {
        return $this->logo ? Storage::disk(config('marketplace.media_disk', 'public'))->url($this->logo) : null;
    }

    /** Masked payout destination for display (never the full number). */
    public function maskedPayout(): string
    {
        $details = (array) ($this->payout_details ?? []);
        $number = (string) ($details['account_number'] ?? $details['wallet_number'] ?? '');
        $tail = $number !== '' ? '••••'.mb_substr($number, -4) : '—';

        return (self::PAYOUT_METHODS[$this->payout_method] ?? $this->payout_method).' '.$tail;
    }
}
