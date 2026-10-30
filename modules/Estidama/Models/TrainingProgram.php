<?php

namespace Modules\Estidama\Models;

use App\Models\User;
use App\Services\Payments\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TrainingProgram extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_paid' => 'boolean',
        'price' => 'decimal:2',
        'sequential' => 'boolean',
        'requires_application' => 'boolean',
        'certificate_enabled' => 'boolean',
        'estimated_minutes' => 'integer',
    ];

    public const STATUSES = [
        'open' => 'مفتوح للتسجيل',
        'ongoing' => 'جاري',
        'closed' => 'مغلق',
    ];

    public const LEVELS = [
        'beginner' => 'مبتدئ',
        'intermediate' => 'متوسط',
        'advanced' => 'متقدم',
    ];

    public function trainingCenter(): BelongsTo
    {
        return $this->belongsTo(TrainingCenter::class);
    }

    /** Trainer (role estidama_trainer) responsible for this program. */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** Sections of the course (table program_modules). */
    public function programModules(): HasMany
    {
        return $this->hasMany(ProgramModule::class)->orderBy('order');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(ProgramLesson::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function finalExam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function completionBadge(): BelongsTo
    {
        return $this->belongsTo(Badge::class, 'completion_badge_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(TrainingCertificate::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(TrainingApplication::class);
    }

    public function isFree(): bool
    {
        return ! $this->is_paid || ! Money::isPositive($this->price);
    }

    public function priceLabel(): string
    {
        return $this->isFree() ? 'مجاني' : Money::format($this->price) . ' ج.م';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    public function levelLabel(): ?string
    {
        return self::LEVELS[$this->level] ?? null;
    }

    /**
     * Published lessons in course order (section order, then lesson order).
     *
     * @return Collection<int, ProgramLesson>
     */
    public function orderedLessons(): Collection
    {
        return ProgramLesson::query()
            ->select('program_lessons.*')
            ->join('program_modules', 'program_modules.id', '=', 'program_lessons.program_module_id')
            ->where('program_lessons.training_program_id', $this->getKey())
            ->where('program_lessons.is_published', true)
            ->where('program_modules.is_published', true)
            ->orderBy('program_modules.order')
            ->orderBy('program_modules.id')
            ->orderBy('program_lessons.sort_order')
            ->orderBy('program_lessons.id')
            ->get();
    }

    public function totalMinutes(): int
    {
        return (int) ($this->estimated_minutes ?: $this->lessons()->where('is_published', true)->sum('estimated_minutes'));
    }

    public function imageUrl(): string
    {
        if (! $this->image) {
            return asset('images/bg/estidama.jpeg');
        }
        if (preg_match('#^(https?:)?//#', $this->image) || str_starts_with($this->image, 'images/')) {
            return str_starts_with($this->image, 'images/') ? asset($this->image) : $this->image;
        }

        return Storage::disk('public')->url($this->image);
    }
}
