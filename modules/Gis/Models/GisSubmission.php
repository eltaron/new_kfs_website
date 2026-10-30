<?php

namespace Modules\Gis\Models;

use Modules\Payments\Models\FulfillmentFee;
use Modules\Payments\Models\Transaction;
use App\Models\User;
use App\Concerns\HasPayments;
use App\Contracts\Payable;
use App\Services\Payments\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class GisSubmission extends Model implements Payable
{
    use HasPayments;

    // تعريف المفتاح الأساسي كـ UUID
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'gis_sub_service_id',
        'applicant_info',
        'address_info',
        'request_type',
        'form_data',
        'attachments',
        'payment_status',
        'total_amount',
        'paid_at',
        'status',
        'inspection_date',
        'is_inspection_confirmed',
        'urban_planning',
        'borders',
        'web_map_url',
        'serial_number',
        'fulfillment_status',
        'fulfillment_action',
        'fulfillment_reason',
        'fulfillment_fee_amount',
        'fulfillment_data_fields',
        'fulfillment_requested_by',
        'fulfillment_requested_at',
        'fulfillment_completed_at',
    ];

    protected $casts = [
        'applicant_info' => 'array',
        'address_info' => 'array',
        'form_data' => 'array',
        'attachments' => 'array',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'fulfillment_fee_amount' => 'decimal:2',
        'urban_planning' => 'array',
        'borders' => 'array',
        'inspection_date' => 'datetime',
        'fulfillment_data_fields' => 'array',
        'fulfillment_requested_at' => 'datetime',
        'fulfillment_completed_at' => 'datetime',
    ];

    /**
     * توليد كود الطلب الفريد تلقائياً (مثل: f85d-4a11-...)
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subService(): BelongsTo
    {
        return $this->belongsTo(GisSubService::class, 'gis_sub_service_id');
    }

    public function fulfillmentFees(): MorphMany
    {
        return $this->morphMany(FulfillmentFee::class, 'feeable');
    }

    public function openFulfillmentFee(): ?FulfillmentFee
    {
        return FulfillmentFee::openFor($this);
    }

    // ── Payable ─────────────────────────────────────────────────────────

    public function payableAmount(): string
    {
        return Money::format($this->total_amount);
    }

    public function payableDescription(): string
    {
        return 'خدمة مكانية: ' . ($this->subService?->name ?? 'خدمة') . ' — ' . ($this->serial_number ?: substr((string) $this->id, 0, 8));
    }

    public function payableOwner(): ?User
    {
        return $this->user;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canBePaid(): bool
    {
        return ! $this->isPaid() && $this->status !== 'rejected' && Money::isPositive($this->total_amount);
    }

    public function markPaid(Transaction $transaction): void
    {
        $this->forceFill(['payment_status' => 'paid', 'paid_at' => now()])->save();
    }

    public function markPaymentFailed(Transaction $transaction): void
    {
        // payment_status stays "pending" so the citizen can retry from the dashboard.
    }

    public function paymentReturnUrl(Transaction $transaction): string
    {
        return route('gis.apply.success', $this->id);
    }
}
