@props(['width' => 220, 'light' => false])
{{-- KFS Heritage ornament: double gold line + boat-frame diamond + lotus bud, drawn on reveal (stroke-dashoffset). --}}
<svg {{ $attributes->class(['kfs-heritage-orn', 'is-light' => $light]) }} data-draw viewBox="0 0 220 24" width="{{ $width }}" height="{{ round($width * 24 / 220) }}" aria-hidden="true" focusable="false">
    <path pathLength="1" d="M2 10 H86 M2 14 H86 M134 10 H218 M134 14 H218" />
    <path pathLength="1" d="M110 2 L124 12 L110 22 L96 12 Z" />
    <path pathLength="1" d="M110 7 C106 10 106 14 110 17 C114 14 114 10 110 7 Z" />
    <circle cx="90" cy="12" r="2" />
    <circle cx="130" cy="12" r="2" />
</svg>
