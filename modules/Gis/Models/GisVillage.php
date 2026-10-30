<?php

namespace Modules\Gis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GisVillage extends Model
{
    protected $fillable = ['gis_shiakha_id', 'name', 'is_ezba'];

    protected $casts = [
        'is_ezba' => 'boolean',
    ];

    public function shiakha(): BelongsTo
    {
        return $this->belongsTo(GisShiakha::class, 'gis_shiakha_id');
    }
}
