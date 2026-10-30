<?php

namespace Modules\Estidama\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramLesson extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_preview' => 'boolean',
        'is_published' => 'boolean',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'estimated_minutes' => 'integer',
    ];

    public const TYPES = [
        'video' => 'فيديو',
        'pdf' => 'ملف PDF',
        'text' => 'محتوى نصي',
        'link' => 'رابط خارجي',
        'quiz' => 'اختبار قصير',
    ];

    public const VIDEO_PROVIDERS = [
        'youtube' => 'يوتيوب',
        'vimeo' => 'فيميو',
        'upload' => 'ملف مرفوع (MP4)',
    ];

    public const TYPE_ICONS = [
        'video' => 'fa-circle-play',
        'pdf' => 'fa-file-pdf',
        'text' => 'fa-file-lines',
        'link' => 'fa-arrow-up-right-from-square',
        'quiz' => 'fa-clipboard-question',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProgramLesson $lesson) {
            // Keep the denormalised program id in sync with the section.
            if ($lesson->program_module_id && (! $lesson->training_program_id || $lesson->isDirty('program_module_id'))) {
                $lesson->training_program_id = ProgramModule::whereKey($lesson->program_module_id)->value('training_program_id');
            }
        });
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(ProgramModule::class, 'program_module_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function prerequisite(): BelongsTo
    {
        return $this->belongsTo(self::class, 'prerequisite_lesson_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? (string) $this->type;
    }

    public function icon(): string
    {
        return self::TYPE_ICONS[$this->type] ?? 'fa-book-open';
    }

    /** Privacy-friendly embed URL for YouTube / Vimeo lessons (Arabic captions preferred). */
    public function embedUrl(): ?string
    {
        if ($this->type !== 'video' || blank($this->video_url)) {
            return null;
        }
        $url = (string) $this->video_url;

        if ($this->video_provider === 'youtube' || str_contains($url, 'youtu')) {
            if (preg_match('#(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})#', $url, $m)) {
                return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&cc_load_policy=1&cc_lang_pref=ar&hl=ar';
            }

            return null;
        }
        if ($this->video_provider === 'vimeo' || str_contains($url, 'vimeo')) {
            if (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m)) {
                return 'https://player.vimeo.com/video/' . $m[1] . '?texttrack=ar';
            }
        }

        return null;
    }

    public function isUploadedVideo(): bool
    {
        return $this->type === 'video' && filled($this->file_path) && ($this->video_provider === 'upload' || blank($this->video_url));
    }
}
