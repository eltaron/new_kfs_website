<?php

namespace Modules\Estidama\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'duration_minutes' => 'integer',
        'passing_score' => 'integer',
        'max_attempts' => 'integer',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'allow_review' => 'boolean',
    ];

    public const KINDS = [
        'final' => 'امتحان نهائي',
        'quiz' => 'اختبار قصير (داخل درس)',
    ];

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ExamSubmission::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(ProgramLesson::class);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? 'امتحان';
    }

    public function attemptsLabel(): string
    {
        return $this->max_attempts ? $this->max_attempts . ' محاولات' : 'غير محدود';
    }
}
