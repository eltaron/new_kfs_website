<?php

namespace Modules\Payments\Models;

use Modules\Gis\Models\GisSubmission;
use Modules\Citizen\Models\ServiceSubmission;
use App\Models\User;
use App\Concerns\HasPayments;
use App\Contracts\Payable;
use App\Services\Payments\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * An extra fee requested from the citizen through the "الاستيفاء"
 * (fulfillment) workflow on a ServiceSubmission or GisSubmission.
 */
class FulfillmentFee extends Model implements Payable
{
    use HasPayments;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['feeable_type', 'feeable_id', 'user_id', 'amount', 'reason', 'status', 'requested_by', 'paid_at'];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function feeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create the fee for a submission (cancelling any previous unpaid one)
     * and mirror the amount on the submission.
     */
    public static function requestFor(ServiceSubmission|GisSubmission $submission, string|float $amount, ?string $reason, ?string $requestedBy): self
    {
        static::query()
            ->where('feeable_type', $submission->getMorphClass())
            ->where('feeable_id', (string) $submission->getKey())
            ->where('status', self::STATUS_PENDING)
            ->update(['status' => self::STATUS_CANCELLED]);

        $submission->forceFill(['fulfillment_fee_amount' => Money::format($amount)])->save();

        return static::create([
            'feeable_type' => $submission->getMorphClass(),
            'feeable_id' => (string) $submission->getKey(),
            'user_id' => $submission->user_id,
            'amount' => Money::format($amount),
            'reason' => $reason,
            'status' => self::STATUS_PENDING,
            'requested_by' => $requestedBy,
        ]);
    }

    /** The open fee of a submission, if any. */
    public static function openFor(Model $submission): ?self
    {
        return static::query()
            ->where('feeable_type', $submission->getMorphClass())
            ->where('feeable_id', (string) $submission->getKey())
            ->where('status', self::STATUS_PENDING)
            ->latest('id')
            ->first();
    }

    // ── Payable ─────────────────────────────────────────────────────────

    public function payableAmount(): string
    {
        return Money::format($this->amount);
    }

    public function payableDescription(): string
    {
        $parent = $this->feeable;
        $ref = match (true) {
            $parent instanceof ServiceSubmission => 'طلب خدمة #' . $parent->id,
            $parent instanceof GisSubmission => 'خدمة مكانية ' . ($parent->serial_number ?: substr((string) $parent->id, 0, 8)),
            default => '',
        };

        return trim('رسوم استيفاء ' . $ref . ($this->reason ? ' — ' . $this->reason : ''));
    }

    public function payableOwner(): ?User
    {
        return $this->user_id ? User::find($this->user_id) : null;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function canBePaid(): bool
    {
        return $this->status === self::STATUS_PENDING && Money::isPositive($this->amount);
    }

    public function markPaid(Transaction $transaction): void
    {
        $this->forceFill(['status' => self::STATUS_PAID, 'paid_at' => now()])->save();

        $parent = $this->feeable;
        if ($parent && $parent->fulfillment_action === 'payment' && $parent->fulfillment_status === 'requested') {
            $parent->forceFill([
                'fulfillment_status' => 'completed',
                'fulfillment_completed_at' => now(),
            ])->save();
        }
    }

    public function markPaymentFailed(Transaction $transaction): void
    {
        // Fee stays pending so the citizen can retry.
    }

    public function paymentReturnUrl(Transaction $transaction): string
    {
        return route('citizen.dashboard.fulfillment');
    }
}
