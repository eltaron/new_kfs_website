<?php

namespace Modules\Estidama\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One attempt at an exam or lesson quiz (in progress, submitted or awaiting
 * manual grading). Legacy rows have status "submitted".
 */
class ExamSubmission extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'answers' => 'array',
        'question_order' => 'array',
        'option_order' => 'array',
        'passed' => 'boolean',
        'score' => 'integer',
        'earned_points' => 'float',
        'total_points' => 'float',
        'attempt_number' => 'integer',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
    ];

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_NEEDS_GRADING = 'needs_grading';

    public const STATUSES = [
        'in_progress' => 'جارٍ الحل',
        'submitted' => 'تم التسليم',
        'needs_grading' => 'بانتظار التصحيح',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(ProgramLesson::class, 'program_lesson_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_NEEDS_GRADING], true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    public function remainingSeconds(): int
    {
        if (! $this->expires_at) {
            return PHP_INT_MAX;
        }

        return (int) max(0, floor(now()->diffInSeconds($this->expires_at, false)));
    }
}
