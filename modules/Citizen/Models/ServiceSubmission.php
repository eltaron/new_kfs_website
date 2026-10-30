<?php

namespace Modules\Citizen\Models;

use Modules\Payments\Models\FulfillmentFee;
use Modules\Payments\Models\Transaction;
use App\Models\User;
use App\Concerns\HasPayments;
use App\Contracts\Payable;
use App\Services\Payments\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ServiceSubmission extends Model implements Payable
{
    use HasFactory, HasPayments;

    protected $guarded = [];
    protected $casts = [
        'submitted_data' => 'array',
        'paid_at'      => 'datetime',
        'fulfillment_data_fields' => 'array',
        'fulfillment_requested_at' => 'datetime',
        'fulfillment_completed_at' => 'datetime',
        'fulfillment_fee_amount' => 'decimal:2',
    ];

    /** Latest completed payment (kept for backwards compatibility). */
    public function transaction()
    {
        return $this->completedTransaction();
    }
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
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
        return 'رسوم خدمة: ' . ($this->service?->title ?? 'خدمة إلكترونية') . ' — طلب #' . $this->id;
    }

    public function payableOwner(): ?User
    {
        return $this->user;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid' || $this->status === 'paid' || $this->paid_at !== null;
    }

    public function canBePaid(): bool
    {
        return ! $this->isPaid()
            && ! in_array($this->status, ['rejected', 'completed'], true)
            && Money::isPositive($this->total_amount);
    }

    public function markPaid(Transaction $transaction): void
    {
        $this->forceFill([
            'payment_status' => 'paid',
            'status' => in_array($this->status, ['awaiting_payment', 'pending', 'payment_failed', 'failed'], true) ? 'paid' : $this->status,
            'paid_at' => now(),
            'payment_request_number' => $transaction->payment_request_number,
            'authorization_code' => $transaction->authorization_code,
            'transaction_number' => $transaction->gateway_transaction_id,
        ])->save();
    }

    public function markPaymentFailed(Transaction $transaction): void
    {
        if (! $this->isPaid()) {
            // status stays "awaiting_payment" so the citizen can retry.
            $this->forceFill(['payment_status' => 'failed'])->save();
        }
    }

    public function paymentReturnUrl(Transaction $transaction): string
    {
        return route('services.success', $this->id);
    }
}
