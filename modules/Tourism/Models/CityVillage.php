<?php

namespace Modules\Tourism\Models;

use Modules\Content\Models\Center;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CityVillage extends Model
{
    use HasFactory;

    protected $fillable = ['center_id', 'name', 'type', 'is_active', 'sort_order'];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** The factory stays in database/factories (legacy location, see docs/architecture.md). */
    protected static function newFactory()
    {
        return \Database\Factories\CityVillageFactory::new();
    }
}
