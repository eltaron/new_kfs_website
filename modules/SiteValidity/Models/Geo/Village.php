<?php

namespace Modules\SiteValidity\Models\Geo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** قراءة فقط لجدول المدن والقرى المشترك (city_villages). */
class Village extends Model
{
    protected $table = 'city_villages';

    protected $guarded = ['*'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('type')->orderBy('sort_order')->orderBy('name');
    }

    public function typeLabel(): string
    {
        return $this->type === 'city' ? 'مدينة' : 'قرية';
    }
}
