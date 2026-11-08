<?php

namespace Modules\Investment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A site where a «حياة كريمة» sector project is implemented (shown on maps). */
class HayahKarimaLocation extends Model
{
    protected $fillable = [
        'hayah_karima_project_id',
        'name',
        'center',
        'village',
        'latitude',
        'longitude',
        'status',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'sort_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(HayahKarimaProject::class, 'hayah_karima_project_id');
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null
            && abs($this->latitude) <= 90 && abs($this->longitude) <= 180
            && ($this->latitude != 0 || $this->longitude != 0);
    }
}
