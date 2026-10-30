<?php

namespace Modules\Content\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Comment extends Model
{
    use HasFactory, HasUuids;

    /**
     * is_approved is intentionally NOT fillable so public comment submissions
     * can never self-approve. Moderation from Filament is allowed through
     * isFillable() below (only while a Filament panel is serving the request).
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'author_name',
        'author_email',
        'content',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function isFillable($key)
    {
        if ($key === 'is_approved') {
            return \Filament\Facades\Filament::isServing();
        }

        return parent::isFillable($key);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The factory stays in database/factories (legacy location, see docs/architecture.md). */
    protected static function newFactory()
    {
        return \Database\Factories\CommentFactory::new();
    }
}
