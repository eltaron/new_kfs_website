<?php

namespace Modules\SiteValidity\Models\Geo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * قراءة فقط لجدول المراكز المشترك (centers) — نفس بيانات بلاغ الطوارئ ودليل المناطق.
 * نموذج قراءة داخل الوحدة حتى لا نعتمد على نماذج وحدة أخرى (docs/architecture.md).
 */
class Centre extends Model
{
    protected $table = 'centers';

    protected $guarded = ['*'];

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class, 'center_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
