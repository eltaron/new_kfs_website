<?php

namespace Modules\Tourism\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CityGuideCategory extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}
