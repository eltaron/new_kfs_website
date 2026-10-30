<?php

namespace Modules\Payments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One payment attempt against the e-Finance gateway.
 *
 * @property string $id
 * @property string|null $user_id
 * @property string $transactionable_type
 * @property string $transactionable_id
 * @property string $amount
 * @property string $status pending|completed|failed|expired
 * @property string|null $sender_request_number
 * @property array|null $aes_secret list of base64 key+iv blobs (latest last)
 * @property array|null $raw_response decrypted gateway payload
 */
class Transaction extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_PENDING => 'قيد الانتظار',
        self::STATUS_COMPLETED => 'مكتملة',
        self::STATUS_FAILED => 'فشلت',
        self::STATUS_EXPIRED => 'منتهية الصلاحية',
    ];

    protected $guarded = ['id'];

    protected $hidden = ['aes_secret', 'raw_response'];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'gateway_amount' => 'decimal:2',
        'collection_fees' => 'decimal:2',
        'raw_response' => 'encrypted:array',
        'aes_secret' => 'encrypted:array',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /** Restrict to payables of the given morph aliases / classes. */
    public function scopeForTypes(Builder $query, array $types): Builder
    {
        return $query->whereIn('transactionable_type', $types);
    }

    /** GIS revenue: GIS submissions + fulfillment fees attached to GIS submissions. */
    public function scopeGis(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('transactionable_type', 'gis_submission')
                ->orWhere(function (Builder $q) {
                    $q->where('transactionable_type', 'fulfillment_fee')
                        ->whereIn('transactionable_id', FulfillmentFee::query()
                            ->where('feeable_type', 'gis_submission')
                            ->selectRaw('CAST(id AS CHAR)'));
                });
        });
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Arabic label for the payable type, for admin tables / exports. */
    public function typeLabel(): string
    {
        return match ($this->transactionable_type) {
            'service_submission' => 'طلب خدمة إلكترونية',
            'gis_submission' => 'خدمة مكانية',
            'fulfillment_fee' => 'رسوم استيفاء',
            'landmark_booking' => 'حجز معلم سياحي',
            'tour_booking' => 'حجز شركة سياحية',
            'removal_order' => 'غرامة مخالفة',
            default => class_basename((string) $this->transactionable_type),
        };
    }
}
