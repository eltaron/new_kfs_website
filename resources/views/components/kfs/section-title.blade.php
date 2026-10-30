@props([
    'num' => null,          // kept for backwards compatibility (no longer rendered in v2)
    'eyebrow' => null,      // optional small teal label above the title
    'title' => '',          // plain text (use the slot for rich HTML)
    'subtitle' => null,
    'align' => 'start',     // start | center
    'dark' => false,
    'link' => null,
    'linkText' => 'عرض الكل',
    'id' => null,
])
{{-- v2 section heading: gold bar · H2 · subtitle · optional «عرض الكل» link (docs/kfs-design-system.md §v2) --}}
<div {{ $attributes->class(['kfs-stitle', 'kfs-stitle--center' => $align === 'center', 'kfs-stitle--dark' => $dark]) }} data-reveal>
    <div class="kfs-stitle__text">
        @if ($eyebrow)
            <span class="kfs-stitle__eyebrow">{{ $eyebrow }}</span>
        @endif
        <h2 class="kfs-stitle__title" @if ($id) id="{{ $id }}" @endif>{{ $slot->isEmpty() ? $title : $slot }}</h2>
        @if ($subtitle)
            <p class="kfs-stitle__sub">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($link)
        <a href="{{ $link }}" class="kfs-stitle__link">
            <span>{{ $linkText }}</span><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        </a>
    @endif
    @isset($tools)
        <div class="kfs-stitle__tools">{{ $tools }}</div>
    @endisset
</div>
