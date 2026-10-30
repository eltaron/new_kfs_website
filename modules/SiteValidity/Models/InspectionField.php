<?php

namespace Modules\SiteValidity\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** بند ديناميكي في استمارة معاينة مهندس التنظيم (يحدده مسؤول الخدمة). */
class InspectionField extends Model
{
    public const TYPES = [
        'yes_no' => 'نعم / لا',
        'text' => 'نص قصير',
        'number' => 'رقم',
        'select' => 'قائمة اختيار',
        'textarea' => 'نص طويل',
    ];

    protected $table = 'sv_inspection_fields';

    protected $fillable = ['key', 'label', 'type', 'options', 'help_text', 'is_required', 'is_active', 'sort_order'];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /** @return list<string> */
    public function optionList(): array
    {
        return array_values(array_filter(array_map(
            fn ($o) => trim((string) (is_array($o) ? ($o['value'] ?? $o['label'] ?? '') : $o)),
            (array) $this->options
        ), fn ($o) => $o !== ''));
    }

    /** @return list<mixed> قواعد التحقق للإجابة. */
    public function rules(): array
    {
        $base = $this->is_required ? ['required'] : ['nullable'];

        return array_merge($base, match ($this->type) {
            'yes_no' => ['in:yes,no'],
            'number' => ['numeric', 'between:-1000000,1000000'],
            'select' => ['string', \Illuminate\Validation\Rule::in($this->optionList())],
            'textarea' => ['string', 'max:3000', 'not_regex:/<[^>]*>/'],
            default => ['string', 'max:500', 'not_regex:/<[^>]*>/'],
        });
    }

    public static function displayValue(?string $type, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($type) {
            'yes_no' => $value === 'yes' ? 'نعم' : ($value === 'no' ? 'لا' : (string) $value),
            default => (string) $value,
        };
    }
}
