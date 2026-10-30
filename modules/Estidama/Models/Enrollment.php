<?php

namespace Modules\Estidama\Models;

use App\Models\User;
use App\Concerns\HasPayments;
use App\Contracts\Payable;
use App\Services\Payments\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A learner's seat in a training program. Paid programs are settled through
 * the unified payment service (morph alias "program_enrollment").
 */
class Enrollment extends Model implements Payable
{
    use HasFactory, HasPayments;

    protected $guarded = [];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'paid_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'progress_percent' => 'integer',
        'amount' => 'decimal:2',
    ];

    public const STATUSES = [
        'pending' => 'قيد الانتظار',
        'approved' => 'مقبول',
        'rejected' => 'مرفوض',
        'completed' => 'مكتمل',
    ];

    public const PAYMENT_STATUSES = [
        'free' => 'مجاني',
        'unpaid' => 'بانتظار السداد',
        'paid' => 'مدفوع',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(TrainingApplication::class, 'training_application_id');
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamSubmission::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(TrainingCertificate::class);
    }

    public function lastLesson(): BelongsTo
    {
        return $this->belongsTo(ProgramLesson::class, 'last_lesson_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    public function paymentStatusLabel(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status ?? 'free'] ?? (string) $this->payment_status;
    }

    public function needsPayment(): bool
    {
        return $this->payment_status === 'unpaid' && Money::isPositive($this->amount);
    }

    /** Learner may open the course player. */
    public function canAccessContent(): bool
    {
        return in_array($this->status, ['approved', 'completed'], true) && ! $this->needsPayment();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    // ── Payable ──────────────────────────────────────────────────

    public function payableAmount(): string
    {
        return Money::format($this->amount);
    }

    public function payableDescription(): string
    {
        $title = $this->trainingProgram?->title ?? ('برنامج #' . $this->training_program_id);

        return 'رسوم الالتحاق ببرنامج استدامة التدريبي: ' . $title . ' — تسجيل #' . $this->id;
    }

    public function payableOwner(): ?User
    {
        return $this->user_id ? User::find($this->user_id) : null;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canBePaid(): bool
    {
        return $this->needsPayment() && in_array($this->status, ['approved', 'pending'], true);
    }

    public function markPaid(\Modules\Payments\Models\Transaction $transaction): void
    {
        $this->forceFill([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'status' => $this->status === 'pending' ? 'approved' : $this->status,
        ])->save();
    }

    public function markPaymentFailed(\Modules\Payments\Models\Transaction $transaction): void
    {
        // Stays unpaid; the learner can retry from "تعلّمي".
    }

    public function paymentReturnUrl(\Modules\Payments\Models\Transaction $transaction): string
    {
        return route('lms.course', $this->training_program_id);
    }
}
