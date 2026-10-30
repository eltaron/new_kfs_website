<?php

namespace Modules\SiteValidity\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** نوع الخدمة (يحدده مسؤول الخدمة) ورسومه. */
class ServiceType extends Model
{
    protected $table = 'sv_service_types';

    protected $fillable = ['name', 'slug', 'description', 'fee', 'is_active', 'sort_order'];

    protected $casts = [
        'fee' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(SiteValidityRequest::class, 'service_type_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
