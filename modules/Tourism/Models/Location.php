<?php

namespace Modules\Tourism\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Location extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function cityGuideCategory(): BelongsTo
    {
        return $this->belongsTo(CityGuideCategory::class);
    }
}
