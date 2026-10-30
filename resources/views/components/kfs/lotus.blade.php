@props(['size' => 64])
{{-- Abstract lotus / palm motif (line art, inherits currentColor). Decorative only. --}}
<svg {{ $attributes->class(['kfs-lotus']) }} data-draw viewBox="0 0 64 64" width="{{ $size }}" height="{{ $size }}" aria-hidden="true" focusable="false">
    <path pathLength="1" d="M32 54 C24 44 22 30 32 12 C42 30 40 44 32 54 Z" />
    <path pathLength="1" d="M32 54 C22 50 12 40 10 26 C20 30 28 38 32 54 Z" />
    <path pathLength="1" d="M32 54 C42 50 52 40 54 26 C44 30 36 38 32 54 Z" />
    <path pathLength="1" d="M14 58 H50" />
</svg>
