@props([
    'icon' => 'docs',   // docs | search | calendar | map | box | inbox
    'title' => 'لا توجد بيانات حالياً',
    'text' => null,
    'compact' => false,
])
{{-- Empty state with a small inline identity illustration (papyrus card + sun + water line). --}}
<div {{ $attributes->class(['kfs-empty', 'kfs-empty--compact' => $compact]) }} role="status">
    <svg class="kfs-empty__art" viewBox="0 0 200 150" width="200" height="150" aria-hidden="true" focusable="false">
        <circle class="e-sun" cx="150" cy="38" r="16" />
        <circle class="e-sun-ring" cx="150" cy="38" r="24" />
        <rect class="e-card" x="46" y="26" width="92" height="96" rx="8" />
        <path class="e-fold" d="M118 26 V44 H138" />
        @switch($icon)
            @case('search')
                <circle class="e-mark" cx="88" cy="70" r="16" />
                <path class="e-mark" d="M100 82 L114 96" />
                @break
            @case('calendar')
                <rect class="e-mark" x="66" y="52" width="52" height="46" rx="5" />
                <path class="e-mark" d="M66 64 H118 M78 46 V56 M106 46 V56" />
                <path class="e-gold" d="M78 76 h8 M96 76 h8 M78 86 h8" />
                @break
            @case('map')
                <path class="e-mark" d="M64 56 L80 50 L104 58 L120 52 V92 L104 98 L80 90 L64 96 Z M80 50 V90 M104 58 V98" />
                <circle class="e-gold-fill" cx="92" cy="70" r="4" />
                @break
            @case('box')
                <path class="e-mark" d="M66 62 L92 50 L118 62 V90 L92 102 L66 90 Z M66 62 L92 74 L118 62 M92 74 V102" />
                @break
            @case('inbox')
                <path class="e-mark" d="M64 74 L74 54 H110 L120 74 V96 H64 Z M64 74 H82 L86 82 H98 L102 74 H120" />
                @break
            @default
                <path class="e-mark" d="M64 54 H112 M64 66 H120 M64 78 H104 M64 90 H114" />
                <path class="e-gold" d="M64 104 H90" />
        @endswitch
        <path class="e-wave" d="M10 132 C30 122 50 122 70 132 S110 142 130 132 170 122 190 132" />
        <path class="e-wave e-wave--2" d="M24 142 C40 136 56 136 72 142 S104 148 120 142 152 136 176 142" />
    </svg>
    <h3 class="kfs-empty__title">{{ $title }}</h3>
    @if ($text)
        <p class="kfs-empty__text">{{ $text }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="kfs-empty__actions">{{ $slot }}</div>
    @endif
</div>
