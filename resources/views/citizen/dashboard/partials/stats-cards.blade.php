{{-- v2 quick stats: white cards, tinted icon square, number + label (whole card links to the matching page) --}}
@php
    $dashStats = [
        ['route' => 'citizen.dashboard.services', 'count' => $stats['services'], 'label' => 'خدمات عامة', 'icon' => 'fa-solid fa-briefcase', 'tone' => ''],
        ['route' => 'citizen.dashboard.gis', 'count' => $stats['gis'], 'label' => 'خدمات مكانية', 'icon' => 'fa-solid fa-map-location-dot', 'tone' => ''],
        ['route' => 'citizen.dashboard.estidama', 'count' => $stats['estidama'], 'label' => 'استدامة', 'icon' => 'fa-solid fa-graduation-cap', 'tone' => 'olive'],
        ['route' => 'citizen.dashboard.monitoring', 'count' => $stats['complaints'], 'label' => 'شكاوي', 'icon' => 'fa-solid fa-clipboard-list', 'tone' => 'gold'],
        ['route' => 'citizen.dashboard.monitoring', 'count' => $stats['suggestions'], 'label' => 'مقترحات', 'icon' => 'fa-solid fa-lightbulb', 'tone' => 'gold'],
        ['route' => 'citizen.dashboard.monitoring', 'count' => $stats['contactMessages'], 'label' => 'رسائل تواصل', 'icon' => 'fa-solid fa-envelope', 'tone' => 'dark'],
        ['route' => 'citizen.dashboard.monitoring', 'count' => $stats['emergencyReports'], 'label' => 'بلاغات طوارئ', 'icon' => 'fa-solid fa-shield-halved', 'tone' => 'danger'],
    ];
@endphp
<div class="dash-stats">
    @foreach ($dashStats as $s)
        <a href="{{ route($s['route']) }}" class="dash-stat {{ $s['tone'] ? 'dash-stat--' . $s['tone'] : '' }}">
            <span class="dash-stat__icon" aria-hidden="true"><i class="{{ $s['icon'] }}"></i></span>
            <span>
                <span class="dash-stat__num">{{ $s['count'] }}</span>
                <span class="dash-stat__label">{{ $s['label'] }}</span>
            </span>
        </a>
    @endforeach
</div>
