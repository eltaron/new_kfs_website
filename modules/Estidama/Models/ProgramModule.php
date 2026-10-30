<?php

namespace Modules\Estidama\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A section ("وحدة") of a course. The legacy columns (type, content_path) are
 * kept for old data; learning content lives in ProgramLesson.
 */
class ProgramModule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'unlock_after_days' => 'integer',
        'estimated_minutes' => 'integer',
        'order' => 'integer',
    ];

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(ProgramLesson::class)->orderBy('sort_order')->orderBy('id');
    }
}
