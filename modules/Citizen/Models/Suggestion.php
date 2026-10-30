<?php

namespace Modules\Citizen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'subject', 'phone', 'message',
        'status', 'national_id', 'admin_reply', 'replied_at',
    ];

    protected $casts = [
        'status' => 'string',
        'replied_at' => 'datetime',
    ];

    /** The factory stays in database/factories (legacy location, see docs/architecture.md). */
    protected static function newFactory()
    {
        return \Database\Factories\SuggestionFactory::new();
    }
}
