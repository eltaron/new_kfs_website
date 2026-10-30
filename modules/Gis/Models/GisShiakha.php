<?php

namespace Modules\Gis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GisShiakha extends Model
{
    protected $fillable = ['gis_markaz_id', 'name', 'shiakha_code'];

    public function markaz(): BelongsTo
    {
        return $this->belongsTo(GisMarkaz::class, 'gis_markaz_id');
    }

    /**
     * الوحدة المحلية الواحدة يتبع لها عدة قرى وعزب
     */
    public function villages(): HasMany
    {
        return $this->hasMany(GisVillage::class, 'gis_shiakha_id');
    }
}
