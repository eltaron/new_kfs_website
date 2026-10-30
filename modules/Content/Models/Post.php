<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;

class Post extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'thumbnail',
        'content',
        'is_featured',
        'is_published',
        'published_at',
        'allow_comments',
        'likes_count',
        'shares_count',
        // used by ImportPostsCommand to keep original dates
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'allow_comments' => 'boolean',
        'published_at' => 'date',
    ];
    protected static function booted()
    {
        static::creating(function ($post) {
            if (empty($post->slug)) { // توليد الـ slug فقط إذا كان فارغًا
                $post->slug = Str::slug($post->title);
            }
        });
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'content', 'is_published', 'category_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "An article has been {$eventName}")
            ->dontSubmitEmptyLogs();
    }
    public function scopeOnlyNews(Builder $query): void
    {
        $query->whereHas('category', fn(Builder $q) => $q->where('slug', '!=', 'events'));
    }

    public function scopeOnlyEvents(Builder $query): void
    {
        $query->whereHas('category', fn(Builder $q) => $q->where('slug', 'events'));
    }

    /** The factory stays in database/factories (legacy location, see docs/architecture.md). */
    protected static function newFactory()
    {
        return \Database\Factories\PostFactory::new();
    }
}
