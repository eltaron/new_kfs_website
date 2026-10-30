<?php

namespace Modules\Content\Models;

use Modules\Tourism\Models\CityVillage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Center extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_active', 'sort_order'];

    public function cityVillages(): HasMany
    {
        return $this->hasMany(CityVillage::class);
    }

    /** The factory stays in database/factories (legacy location, see docs/architecture.md). */
    protected static function newFactory()
    {
        return \Database\Factories\CenterFactory::new();
    }
}
