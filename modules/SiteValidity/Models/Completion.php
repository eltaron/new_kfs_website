<?php

namespace Modules\SiteValidity\Models;

use App\Concerns\HasPayments;
use App\Contracts\Payable;
use App\Models\User;
use App\Services\Payments\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\SiteValidity\Services\WorkflowService;
use Modules\SiteValidity\Support\SvStatus;

/**
 * جولة «استيفاء»: ما طلبه المركز الجيومكاني/مهندس التنظيم ورد المواطن.
 * النوع «رسوم»: المواطن يسدد المبلغ إلكترونياً ويُغلق الاستيفاء تلقائياً عند تأكيد الدفع.
 *
 * Payable: route('payments.pay', ['type' => 'site_validity_completion', 'id' => $completion->id])
 */
class Completion extends Model implements Payable
{
    use HasPayments {
        isPaid as protected hasCompletedTransaction;
    }

    public const TYPE_DOCUMENTS = 'documents';
    public const TYPE_PAYMENT = 'payment';
    public const TYPES = [self::TYPE_DOCUMENTS => 'استكمال مستندات / بيانات', self::TYPE_PAYMENT => 'سداد رسوم'];

    protected $table = 'sv_completions';

    protected $fillable = ['round', 'type', 'amount', 'notes', 'requested_items'];

    protected $casts = [
        'requested_items' => 'array',
        'requested_at' => 'datetime',
        'replied_at' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'round' => 'integer',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SiteValidityRequest::class, 'request_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(RequestFile::class, 'completion_id');
    }

    public function isOpen(): bool
    {
        return $this->replied_at === null;
    }

    public function isPayment(): bool
    {
        return $this->type === self::TYPE_PAYMENT;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type ?? self::TYPE_DOCUMENTS] ?? (string) $this->type;
    }

    // ───────────────────────────── Payable

    public function payableAmount(): string
    {
        return Money::format($this->amount ?? 0);
    }

    public function payableDescription(): string
    {
        return 'رسوم استيفاء (الجولة ' . $this->round . ') لطلب صلاحية الموقع رقم ' . ($this->request?->number ?: $this->request_id);
    }

    public function payableOwner(): ?User
    {
        return $this->request?->user;
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null || ($this->exists && $this->hasCompletedTransaction());
    }

    public function canBePaid(): bool
    {
        return $this->isPayment()
            && $this->isOpen()
            && ! $this->isPaid()
            && $this->request?->status === SvStatus::COMPLETION_REQUESTED
            && Money::isPositive($this->payableAmount());
    }

    /** المعاملة بدون نوع صريح (مثل SiteValidityRequest) حتى لا تعتمد الوحدة على نماذج وحدة الدفع. */
    public function markPaid($transaction): void
    {
        if ($this->paid_at !== null) {
            return;
        }

        $this->forceFill([
            'paid_at' => now(),
            'paid_amount' => Money::format($transaction->amount ?? $this->amount),
            'payment_reference' => (string) ($transaction->sender_request_number ?? $transaction->getKey()),
        ])->save();

        app(WorkflowService::class)->settleCompletionPayment($this);
    }

    public function markPaymentFailed($transaction): void
    {
        if ($this->paid_at !== null || $this->request === null) {
            return;
        }

        app(WorkflowService::class)->log($this->request, null, 'payment_failed', $this->request->status, $this->request->status,
            'رسوم الاستيفاء (الجولة ' . $this->round . '): ' . (string) ($transaction->failure_reason ?? 'لم تكتمل عملية الدفع.'));
    }

    public function paymentReturnUrl($transaction): string
    {
        return route('site-validity.requests.show', $this->request_id);
    }
}
