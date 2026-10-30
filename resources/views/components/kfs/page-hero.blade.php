@props([
    'title' => '',
    'subtitle' => null,     // lead paragraph under the title (alias: lead)
    'lead' => null,
    'image' => null,        // optional background photo URL — sits under the dark-green identity overlay
    'kicker' => null,       // small gold label above the title (alias: eyebrow)
    'eyebrow' => null,
    'icon' => null,         // optional Font Awesome class shown in a soft square
    'crumbs' => [],         // after «الرئيسية»: ['label' => 'url-or-null', ...]  or  [['label', 'url-or-null'], ...]
    'current' => null,      // breadcrumb label for the current page (defaults to the title, trimmed)
    'size' => 'default',    // default | compact (forms, dashboards, auth) | detail (articles, records)
    'wave' => true,         // static wave base (gold line + page-colour fill)
    'waveFill' => 'var(--kfs-page-bg, var(--kfs-bg))',
    'container' => 'container', // wrapper class (e.g. "mkt-container" inside the store)
    'tag' => 'header',
])
@php
    $lead = $subtitle ?? $lead;
    $kicker = $kicker ?? $eyebrow;
    $size = in_array($size, ['default', 'compact', 'detail'], true) ? $size : 'default';
    $heading = $slot->isEmpty() ? $title : $slot;
    $currentLabel = \Illuminate\Support\Str::limit(trim(strip_tags($current ?? ($title ?: (string) $slot))), 60);
    $trail = [];
    foreach ((array) $crumbs as $key => $value) {
        if (is_array($value)) {
            $trail[] = [$value[0] ?? ($value['label'] ?? ''), $value[1] ?? ($value['url'] ?? null)];
        } else {
            $trail[] = [$key, $value];
        }
    }
    $hasMeta = isset($meta) && trim((string) $meta) !== '';
    $hasActions = isset($actions) && trim((string) $actions) !== '';
    // Optional raw trail: <x-slot:breadcrumb><li class="breadcrumb-item">…</li>…</x-slot> (must include the current item).
    $hasCustomTrail = isset($breadcrumb) && trim((string) $breadcrumb) !== '';
@endphp
{{-- KFS inner-page hero (single hero for every page except home): dark-green identity band,
     breadcrumb first, start-aligned title, optional photo / meta / actions, static wave base. --}}
<{{ $tag }} {{ $attributes->class([
    'kfs-page-hero',
    'kfs-page-hero--' . $size,
    'has-image' => (bool) $image,
    'has-wave' => $wave,
]) }}>
    <div class="kfs-page-hero__bg" aria-hidden="true">
        @if ($image)
            <span class="kfs-page-hero__img" style="background-image: url('{{ $image }}');"></span>
        @endif
    </div>
    <div class="{{ $container }} kfs-page-hero__container">
        <nav class="kfs-page-hero__nav" aria-label="مسار التنقل">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> الرئيسية</a></li>
                @if ($hasCustomTrail)
                    {{ $breadcrumb }}
                @else
                @foreach ($trail as [$label, $url])
                    @if ($label !== '' && $label !== null)
                        <li class="breadcrumb-item">
                            @if ($url)<a href="{{ $url }}">{{ $label }}</a>@else<span>{{ $label }}</span>@endif
                        </li>
                    @endif
                @endforeach
                <li class="breadcrumb-item active" aria-current="page">{{ $currentLabel }}</li>
                @endif
            </ol>
        </nav>
        <div class="kfs-page-hero__inner kfs-hero-anim">
            @if ($icon)
                <span class="kfs-page-hero__icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
            @endif
            <div class="kfs-page-hero__text">
                @if ($kicker)
                    <span class="kfs-page-hero__kicker">{{ $kicker }}</span>
                @endif
                <h1 class="kfs-page-hero__title">{{ $heading }}</h1>
                @if ($lead)
                    <p class="kfs-page-hero__lead">{{ $lead }}</p>
                @endif
                @if ($hasMeta)
                    <div class="kfs-page-hero__meta">{{ $meta }}</div>
                @endif
            </div>
            @if ($hasActions)
                <div class="kfs-page-hero__actions">{{ $actions }}</div>
            @endif
        </div>
    </div>
    @if ($wave)
        <x-kfs.wave class="kfs-page-hero__wave" :fill="$waveFill" :animated="false" :gold="true" />
    @endif
</{{ $tag }}>
