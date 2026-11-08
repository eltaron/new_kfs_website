@extends('layouts.app')
@php
    $mapPoints = $sector->locations->filter->hasCoordinates()->map(fn ($l) => [
        'name' => $l->name,
        'place' => trim(collect([$l->village, $l->center])->filter()->implode('، ')),
        'status' => $l->status,
        'lat' => $l->latitude,
        'lng' => $l->longitude,
    ])->values();
    $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/div|\/li)[^>]*>/i', ' ', (string) $sector->description)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    $progress = max(0, min(100, (int) $sector->progress));
    $centers = $sector->locations->pluck('center')->filter()->unique();
@endphp
@section('title', 'قطاع ' . $sector->sector_name . ' — حياة كريمة')
@section('description', \Illuminate\Support\Str::limit($plain ?: 'إنجازات قطاع ' . $sector->sector_name . ' ضمن المبادرة الرئاسية حياة كريمة في محافظة كفر الشيخ.', 160, '…'))
@if ($sector->image)
    @section('og_image', asset(Storage::url($sector->image)))
@endif

@push('css')
    @if ($mapPoints->isNotEmpty())
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endif
    <link rel="stylesheet" href="{{ asset('css/kfs-pages-info.css') }}?v={{ @filemtime(public_path('css/kfs-pages-info.css')) }}">
@endpush

@section('content')
    <main class="kpi khk">
        <x-kfs.page-hero size="compact" :title="'قطاع ' . $sector->sector_name" kicker="المبادرة الرئاسية حياة كريمة"
            :icon="$sector->icon ?: 'fa-solid fa-hand-holding-heart'"
            :crumbs="['المشروعات' => route('projects.index'), 'حياة كريمة' => route('projects.hay-karima')]" :current="$sector->sector_name" />

        <div class="container kpi__wrap">
            {{-- Progress + figures --}}
            <section class="khk-summary">
                <div class="khk-ring" style="--p: {{ $progress }}" role="img" aria-label="نسبة التنفيذ {{ $progress }}%">
                    <span><strong>{{ $progress }}%</strong><small>نسبة التنفيذ</small></span>
                </div>
                <div class="khk-summary__body">
                    <h2 class="khk-summary__title">
                        <i class="{{ $sector->icon ?: 'fa-solid fa-hand-holding-heart' }}" aria-hidden="true"></i> قطاع {{ $sector->sector_name }}
                    </h2>
                    <div class="khk-facts">
                        <span><strong>{{ $sector->locations->count() }}</strong> موقع تنفيذ</span>
                        <span><strong>{{ $mapPoints->count() }}</strong> محدد على الخريطة</span>
                        @if ($centers->isNotEmpty())
                            <span><strong>{{ $centers->count() }}</strong> {{ $centers->count() > 1 ? 'مراكز' : 'مركز' }}</span>
                        @endif
                    </div>
                    @if ($sector->description)
                        <div class="klm-prose khk-desc">{!! clean($sector->description, 'cms') !!}</div>
                    @endif
                </div>
            </section>

            {{-- Sites --}}
            <section aria-labelledby="khkSitesTitle">
                <div class="khk-sites__head">
                    <x-kfs.section-title id="khkSitesTitle" title="مواقع التنفيذ" subtitle="أماكن مشروعات القطاع على أرض المحافظة." />
                    @if ($mapPoints->isNotEmpty() && Route::has('governorate.map'))
                        <a href="{{ route('governorate.map', ['group' => 'hayah', 'category' => $sector->sector_name]) }}" class="kpi-btn">
                            <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i><span>عرض على الخريطة التفاعلية</span>
                        </a>
                    @endif
                </div>

                @if ($sector->locations->isNotEmpty())
                    <div class="khk-sites {{ $mapPoints->isNotEmpty() ? 'khk-sites--map' : '' }}">
                        <ul class="khk-list">
                            @foreach ($sector->locations as $i => $loc)
                                <li>
                                    <button type="button" class="khk-site" @if ($loc->hasCoordinates()) data-point="{{ $mapPoints->search(fn ($p) => $p['name'] === $loc->name && $p['lat'] == $loc->latitude) }}" @else disabled @endif>
                                        <span class="khk-site__num">{{ $loop->iteration }}</span>
                                        <span class="khk-site__body">
                                            <strong>{{ $loc->name }}</strong>
                                            @if ($loc->village || $loc->center)
                                                <small><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ collect([$loc->village, $loc->center])->filter()->implode('، ') }}</small>
                                            @endif
                                        </span>
                                        @if ($loc->status)
                                            <span class="kpi-chip {{ $loc->status === 'تم التنفيذ' ? '' : 'kpi-chip--gold' }}">{{ $loc->status }}</span>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        @if ($mapPoints->isNotEmpty())
                            <div class="khk-map" id="khkMap" role="region" aria-label="خريطة مواقع قطاع {{ $sector->sector_name }}"></div>
                        @endif
                    </div>
                @else
                    <div class="khk-empty">
                        <i class="fa-solid fa-map-pin" aria-hidden="true"></i>
                        <p>لم تُضف مواقع التنفيذ لهذا القطاع بعد.</p>
                    </div>
                @endif
            </section>

            {{-- Other sectors --}}
            @if ($others->isNotEmpty())
                <section aria-labelledby="khkOthersTitle">
                    <x-kfs.section-title id="khkOthersTitle" title="قطاعات أخرى" :link="route('projects.hay-karima')" linkText="كل القطاعات" />
                    <div class="khk-others">
                        @foreach ($others as $o)
                            <a href="{{ route('projects.hay-karima.show', $o->slug) }}" class="khk-other">
                                <span class="khk-other__icon" aria-hidden="true"><i class="{{ $o->icon ?: 'fa-solid fa-hand-holding-heart' }}"></i></span>
                                <strong>{{ $o->sector_name }}</strong>
                                <span class="khk-other__bar"><span style="width: {{ max(0, min(100, (int) $o->progress)) }}%"></span></span>
                                <small>{{ (int) $o->progress }}%</small>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </main>
@endsection

@push('scripts')
    @if ($mapPoints->isNotEmpty())
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script type="application/json" id="khkData">@json($mapPoints)</script>
        <script>
        (function () {
            const pts = JSON.parse(document.getElementById('khkData').textContent);
            const map = L.map('khkMap', { scrollWheelZoom: false });
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
            }).addTo(map);
            const icon = L.divIcon({ className: 'kprj-pin-wrap', html: '<span class="kprj-pin khk-pin"><i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i></span>', iconSize: [36, 44], iconAnchor: [18, 42], popupAnchor: [0, -38] });
            const markers = pts.map(p => {
                const box = document.createElement('div'); box.className = 'kprj-pop';
                const b = document.createElement('strong'); b.textContent = p.name; box.append(b);
                if (p.place) { const s = document.createElement('div'); s.textContent = p.place; box.append(s); }
                return L.marker([p.lat, p.lng], { icon, title: p.name }).addTo(map).bindPopup(box);
            });
            map.fitBounds(L.latLngBounds(pts.map(p => [p.lat, p.lng])).pad(0.3), { maxZoom: 14 });
            document.querySelectorAll('.khk-site[data-point]').forEach(btn => btn.addEventListener('click', () => {
                const m = markers[+btn.dataset.point]; if (!m) return;
                map.flyTo(m.getLatLng(), 15, { duration: .7 }); setTimeout(() => m.openPopup(), 750);
                document.querySelectorAll('.khk-site').forEach(x => x.classList.toggle('is-active', x === btn));
                if (window.matchMedia('(max-width: 991.98px)').matches) document.getElementById('khkMap').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }));
        })();
        </script>
    @endif
@endpush
