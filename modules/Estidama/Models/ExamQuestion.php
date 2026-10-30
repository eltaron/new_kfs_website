<?php

namespace Modules\Estidama\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestion extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'options' => 'array', // [{option_text: "..."}, ...]
        'points' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'question_type' => 'mcq',
        'points' => 1,
    ];

    public const TYPES = [
        'mcq' => 'اختيار من متعدد',
        'true_false' => 'صح / خطأ',
        'essay' => 'سؤال مقالي (تصحيح يدوي)',
    ];

    public const TRUE_FALSE_OPTIONS = ['صح', 'خطأ'];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function isEssay(): bool
    {
        return $this->question_type === 'essay';
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->question_type ?? 'mcq'] ?? 'اختيار من متعدد';
    }

    /** @return list<string> */
    public function optionTexts(): array
    {
        if ($this->question_type === 'true_false') {
            return self::TRUE_FALSE_OPTIONS;
        }

        return collect($this->options ?? [])
            ->map(fn ($o) => is_array($o) ? ($o['option_text'] ?? null) : $o)
            ->filter(fn ($o) => filled($o))
            ->map(fn ($o) => (string) $o)
            ->values()
            ->all();
    }

    public function isCorrect(mixed $answer): bool
    {
        if ($this->isEssay() || $answer === null || $answer === '') {
            return false;
        }

        return trim((string) $answer) === trim((string) $this->correct_answer);
    }
}
