@extends('layouts.app')
@section('title', 'المشروعات القومية | محافظة كفر الشيخ')
@section('description', 'المشروعات القومية والتنموية في محافظة كفر الشيخ وتوزيعها الجغرافي على المراكز.')

@php
    $mapData = $projects->getCollection()
        ->filter(fn ($p) => $p->latitude && $p->longitude && $p->slug)
        ->map(fn ($p) => [
            'id' => $p->id,
            'name' => trim($p->name),
            'lat' => (float) $p->latitude,
            'lng' => (float) $p->longitude,
            'url' => route('projects.show', $p->slug),
            'thumb' => $p->thumbnail ? Storage::url($p->thumbnail) : null,
        ])->values();
    $plain = fn ($html, $n) => \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), $n);
    $totalImages = $projects->getCollection()->sum('images_count') + $projects->getCollection()->filter(fn ($p) => $p->thumbnail)->count();
    $programs = collect([
        ['projects.hay-karima', 'fa-solid fa-hand-holding-heart', 'مبادرة حياة كريمة', $programCounts['حياة كريمة'] ?? null, 'تطوير قرى الريف المصري'],
        ['investments.index', 'fa-solid fa-chart-line', 'الاستثمار والفرص', ($programCounts['استثماري'] ?? 0) + ($programCounts['صناعي'] ?? 0), 'فرص استثمارية ومناطق صناعية'],
        ['governorate.map', 'fa-solid fa-map-location-dot', 'الخريطة التفاعلية', null, 'كل المشروعات والمعالم على خريطة واحدة'],
    ])->filter(fn ($p) => Route::has($p[0]));
@endphp

@push('css')
    @if ($mapData->isNotEmpty())
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endif
    <link rel="stylesheet" href="{{ asset('css/kfs-pages-info.css') }}?v={{ @filemtime(public_path('css/kfs-pages-info.css')) }}">
@endpush

@section('content')
    <main class="kpi">
        <x-kfs.page-hero title="المشروعات القومية" kicker="مشروعات المحافظة" icon="fa-solid fa-flag"
            subtitle="مشروعات تنموية كبرى تُنفَّذ على أرض كفر الشيخ — تعرّف عليها وعلى مواقعها."
            :image="asset('images/bg/projects.jpg')" />

        <div class="container kpi__wrap">
            {{-- Figures --}}
            <section class="kpi-figs" aria-label="أرقام المشروعات">
                <div class="kpi-fig"><strong>{{ $projects->total() }}</strong><span>مشروعاً قومياً</span></div>
                <div class="kpi-fig"><strong>{{ $mapData->count() }}</strong><span>محدد على الخريطة</span></div>
                <div class="kpi-fig"><strong>{{ $totalImages }}</strong><span>صورة توثيقية</span></div>
            </section>

            {{-- Projects as editorial rows --}}
            @if ($projects->isNotEmpty())
                <section class="kprj-list" aria-label="قائمة المشروعات">
                    @foreach ($projects as $project)
                        @php $photos = $project->images_count + ($project->thumbnail ? 1 : 0); @endphp
                        <article class="kprj {{ $loop->odd ? '' : 'kprj--alt' }}">
                            <a href="{{ route('projects.show', $project->slug) }}" class="kprj__media" tabindex="-1" aria-hidden="true">
                                <img src="{{ $project->thumbnail ? Storage::url($project->thumbnail) : asset('images/bg/projects.jpg') }}" alt="" loading="lazy">
                                @if ($photos > 1)
                                    <span class="kprj__photos"><i class="fa-solid fa-images"></i> {{ $photos }}</span>
                                @endif
                            </a>
                            <div class="kprj__body">
                                <div class="kprj__chips">
                                    <span class="kpi-chip"><i class="fa-solid fa-flag" aria-hidden="true"></i> مشروع قومي</span>
                                    @if ($project->is_highlighted)
                                        <span class="kpi-chip kpi-chip--gold"><i class="fa-solid fa-star" aria-hidden="true"></i> مميز</span>
                                    @endif
                                    @if ($project->latitude && $project->longitude)
                                        <button type="button" class="kpi-chip kpi-chip--btn" data-focus-project="{{ $project->id }}">
                                            <i class="fa-solid fa-location-dot" aria-hidden="true"></i> على الخريطة
                                        </button>
                                    @endif
                                </div>
                                <h2 class="kprj__title"><a href="{{ route('projects.show', $project->slug) }}">{{ trim($project->name) }}</a></h2>
                                @if ($project->description)
                                    <p class="kprj__text">{{ $plain($project->description, 240) }}</p>
                                @endif
                                <a href="{{ route('projects.show', $project->slug) }}" class="kpi-btn">
                                    <span>تفاصيل المشروع</span><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </section>
                @if ($projects->hasPages())
                    <div class="pagination-wrapper">{{ $projects->links() }}</div>
                @endif
            @else
                <x-kfs.empty-state icon="box" title="لا توجد مشروعات منشورة حالياً" />
            @endif

            {{-- Map --}}
            @if ($mapData->isNotEmpty())
                <section class="kprj-map" aria-labelledby="kprjMapTitle">
                    <x-kfs.section-title id="kprjMapTitle" title="المشروعات على الخريطة" subtitle="اختر مشروعاً لعرض موقعه." />
                    <div class="kprj-map__grid">
                        <ul class="kprj-map__list" id="kprjList">
                            @foreach ($mapData as $p)
                                <li><button type="button" data-focus-project="{{ $p['id'] }}"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $p['name'] }}</button></li>
                            @endforeach
                        </ul>
                        <div class="kprj-map__canvas" id="kprjMap" role="region" aria-label="خريطة المشروعات القومية"></div>
                    </div>
                </section>
            @endif

            {{-- Other programs --}}
            @if ($programs->isNotEmpty())
                <section aria-labelledby="kprjMoreTitle">
                    <x-kfs.section-title id="kprjMoreTitle" title="برامج ومشروعات أخرى" />
                    <div class="kpi-links">
                        @foreach ($programs as [$route, $icon, $title, $count, $desc])
                            <a href="{{ route($route) }}" class="kpi-link">
                                <span class="kpi-link__icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                                <span class="kpi-link__body"><strong>{{ $title }} @if ($count)<small class="kpi-link__count">{{ $count }}</small>@endif</strong><small>{{ $desc }}</small></span>
                                <i class="fa-solid fa-arrow-left kpi-link__go" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </main>
@endsection

@push('scripts')
    @if ($mapData->isNotEmpty())
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script type="application/json" id="kprjData">@json($mapData)</script>
        <script>
        (function () {
            const points = JSON.parse(document.getElementById('kprjData').textContent);
            const map = L.map('kprjMap', { scrollWheelZoom: false });
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
            }).addTo(map);
            const icon = L.divIcon({ className: 'kprj-pin-wrap', html: '<span class="kprj-pin"><i class="fa-solid fa-flag" aria-hidden="true"></i></span>', iconSize: [36, 44], iconAnchor: [18, 42], popupAnchor: [0, -38] });
            const markers = {};
            points.forEach(p => {
                const box = document.createElement('div'); box.className = 'kprj-pop';
                if (p.thumb) { const img = document.createElement('img'); img.src = p.thumb; img.alt = ''; box.append(img); }
                const a = document.createElement('a'); a.href = p.url; a.textContent = p.name; box.append(a);
                markers[p.id] = L.marker([p.lat, p.lng], { icon, title: p.name }).addTo(map).bindPopup(box);
            });
            map.fitBounds(L.latLngBounds(points.map(p => [p.lat, p.lng])).pad(0.25), { maxZoom: 12 });

            document.querySelectorAll('[data-focus-project]').forEach(btn => btn.addEventListener('click', () => {
                const m = markers[btn.dataset.focusProject];
                if (!m) return;
                document.getElementById('kprjMap').scrollIntoView({ behavior: 'smooth', block: 'center' });
                map.flyTo(m.getLatLng(), 14, { duration: .8 });
                setTimeout(() => m.openPopup(), 850);
                document.querySelectorAll('#kprjList button').forEach(b => b.classList.toggle('is-active', b.dataset.focusProject === btn.dataset.focusProject));
            }));
        })();
        </script>
    @endif
@endpush
