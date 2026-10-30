@props([
    'value' => 0,
    'label' => '',
    'icon' => null,
    'prefix' => '',
    'suffix' => '',
])
@php
    $raw = is_numeric($value) ? $value + 0 : (float) preg_replace('/[^\d.]/', '', (string) $value);
    $isNum = is_numeric($value) || $raw > 0;
    $dec = (floor($raw) != $raw) ? 1 : 0;
@endphp
{{-- v2 stat card: tinted icon square · number (server-rendered, counted up by kfs-motion.js) · label --}}
<div {{ $attributes->class(['kfs-stat2']) }}>
    @if ($icon)
        <span class="kfs-stat2__icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
    @endif
    <div class="kfs-stat2__body">
        <div class="kfs-stat2__num">
            @if ($prefix)<span class="kfs-stat2__affix">{{ $prefix }}</span>@endif
            @if ($isNum)
                <span class="kfs-count" data-count="{{ $raw }}" data-decimals="{{ $dec }}">{{ number_format($raw, $dec) }}</span>
            @else
                <span>{{ $value }}</span>
            @endif
            @if ($suffix)<span class="kfs-stat2__affix">{{ $suffix }}</span>@endif
        </div>
        <p class="kfs-stat2__label">{{ $label }}</p>
    </div>
</div>
