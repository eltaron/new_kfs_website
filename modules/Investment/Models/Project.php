<?php

namespace Modules\Investment\Models;

use Modules\Content\Models\Image;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Project extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'is_highlighted' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($project) {
            $project->slug = Str::slug($project->name);
        });
    }


    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'is_highlighted', 'investment_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "A project has been {$eventName}")
            ->dontSubmitEmptyLogs();
    }
}
