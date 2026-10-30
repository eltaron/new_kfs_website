<?php

namespace Modules\SiteValidity\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** نوع مرفق يرفعه المركز الجيومكاني بعد المعاينة (الرفع المساحي، ملف الكاد، ...). */
class AttachmentType extends Model
{
    protected $table = 'sv_attachment_types';

    protected $fillable = ['key', 'name', 'description', 'extensions', 'max_size_kb', 'is_required', 'is_active', 'sort_order'];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'max_size_kb' => 'integer',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /** الامتدادات المسموحة = (ما حدده المسؤول) ∩ (القائمة الآمنة في الإعدادات). @return list<string> */
    public function allowedExtensions(): array
    {
        $configured = array_filter(array_map(fn ($e) => strtolower(trim($e, " .\t")), explode(',', (string) $this->extensions)));
        $safe = (array) config('site-validity.staff_extensions_whitelist', []);

        return array_values(array_intersect($configured, $safe)) ?: ['pdf'];
    }
}
