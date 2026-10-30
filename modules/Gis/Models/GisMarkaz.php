<?php

namespace Modules\Gis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GisMarkaz extends Model
{
    protected $fillable = ['name', 'gis_code'];

    public static function cachedOptions(): array
    {
        return cache()->rememberForever('gis_markaz_options', fn() => self::pluck('name', 'name')->toArray());
    }

    public static function cachedIdOptions(): array
    {
        return cache()->rememberForever('gis_markaz_id_options', fn() => self::pluck('name', 'id')->toArray());
    }

    public static function clearCache(): void
    {
        cache()->forget('gis_markaz_options');
        cache()->forget('gis_markaz_id_options');
    }

    protected static function booted(): void
    {
        static::saved(fn() => static::clearCache());
        static::deleted(fn() => static::clearCache());
    }

    /**
     * المركز الواحد يتبع له عدة وحدات محلية / شياخات
     */
    public function shiakhas(): HasMany
    {
        return $this->hasMany(GisShiakha::class, 'gis_markaz_id');
    }
}
