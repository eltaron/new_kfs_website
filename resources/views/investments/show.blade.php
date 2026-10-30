@extends('layouts.app')

@section('title', 'الاستثمار: ' . $investment->title)

{{-- Push the specific CSS for this page --}}
@push('css')
    <link rel="stylesheet" href="{{ asset('css/investments.css') }}?v={{ @filemtime(public_path('css/investments.css')) }}">
    {{-- Leaflet CSS for the map --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')
    <main class="main-content">
        {{-- Page Header --}}
        <x-kfs.page-hero :title="$investment->title" icon="fa-solid fa-chart-line"
            :kicker="$investment->type == 1 ? 'فرص استثمارية' : 'الاستثمار'"
            :image="!empty($investment->thumbnail) ? Storage::url($investment->thumbnail) : asset('images/bg/investment.jpg')"
            :crumbs="['الاستثمار' => route('investments.index')]" />

        <div class="container pb-5">
            <div class="row g-4 g-lg-5">
                {{-- Main Content Column --}}
                <div class="col-lg-{{ $investment->type == 1 ? '7' : '12' }}">
                    <article class="investment-content-wrapper kfs-panel">
                        @if ($investment->type == 1)
                            <h2 class="main-title">مميزات الاستثمار في {{ $investment->title }}</h2>
                        @endif
                        {{-- Rich Text Description --}}
                        @if ($investment->description)
                            <div class="rich-text-content">
                                {!! clean($investment->description, 'cms') !!}
                            </div>
                        @else
                            <p class="text-muted">لا توجد تفاصيل متاحة لهذه الفرصة الاستثمارية حاليًا.</p>
                        @endif
                    </article>
                </div>
                @if ($investment->type == 1)
                    {{-- Sticky Sidebar Column --}}
                    <div class="col-lg-5">
                        <aside class="kfs-aside">
                            {{-- Contact CTA Widget --}}
                            <div class="kfs-panel text-center" style="border-top: 3px solid var(--kfs-gold);">
                                <span class="kfs-stat2__icon mx-auto mb-3" aria-hidden="true"><i class="fa-solid fa-headset"></i></span>
                                <h2 class="h5 fw-bold mb-3">في حالة الرغبة في التقدم لأي فرصة أو وجود أي استفسارات</h2>
                                <a href="{{ route('investment.contact') }}" class="btn-kfs w-100"><span>تواصل معنا من هنا</span><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                            </div>
                        </aside>
                    </div>
                @endif
            </div>
            {{-- Interactive Map for Related Projects --}}
            @if ($investment->projects->isNotEmpty())
                <div class="map-interactive-section mt-5">
                    <x-kfs.section-title :title="$investment->title . ' المتاحة وتوزيعها الجغرافي'" />

                    <div class="map-dynamic-container">
                        {{-- القائمة الجانبية للخريطة --}}
                        <div class="map-projects-sidebar">
                            <div class="sidebar-header">
                                <span>قائمة {{ $investment->title }}</span>
                                <span class="badge bg-gold">{{ count($investment->projects) }} </span>
                            </div>
                            <div class="projects-list" id="projectsSidebarList">
                                {{-- سيتم تعبئة الفرص عبر الـ Javascript --}}
                            </div>
                        </div>

                        {{-- حاوية الخريطة --}}
                        <div class="map-canvas-wrapper">
                            <div id="investmentProjectsMap" style="height: 100%;"></div>
                        </div>
                    </div>
                </div>
            @endif
            {{-- Full-width Embedded IFrame Map (if it exists) --}}
            @if ($investment->map_iframe)
                <div class="map-section mt-5 pt-4">
                    <x-kfs.section-title title="موقع الفرصة على الخريطة" />
                    <div class="map-iframe-wrapper-fullwidth">
                        {!! clean(preg_replace('/(width|height)="[^"]*"/', '', $investment->map_iframe), 'cms') !!}
                    </div>
                </div>
            @endif
        </div>
    </main>
    @php
        $projects = $investment->projects
            ->filter(function ($p) {
                return $p->latitude && $p->longitude;
            })
            ->map(function ($p) {
                return [
                    'name' => $p->name,
                    'lat' => $p->latitude,
                    'lng' => $p->longitude,
                    'slug' => $p->slug,
                ];
            })
            ->values();
    @endphp

@endsection
@push('scripts')
    {{-- Leaflet JS & CSS --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapElement = document.getElementById('investmentProjectsMap');
            const sidebarList = document.getElementById('projectsSidebarList');
            const projects = @json($projects);
            const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

            if (mapElement && projects.length > 0) {
                // استخدام Esri Satellite لمظهر أقوى وأكثر رسمية (يشبه وزارة الاستثمار)
                const map = L.map(mapElement, {
                    scrollWheelZoom: false
                }).setView([31.1143, 30.9416], 12);

                // طبقة خرائط شوارع وأسماء عربية واضحة
                L.tileLayer(
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
                        attribution: 'Tiles &copy; Esri'
                    }).addTo(map);

                const markers = {};

                projects.forEach((project, index) => {
                    // 1. إضافة المشروع للقائمة الجانبية (Sidebar)
                    const card = document.createElement('div');
                    card.className = 'project-item-card';
                    card.innerHTML = `
                    <h4><i class="fas fa-location-dot me-1 text-gold"></i> ${esc(project.name)}</h4>
                    <span class="view-link">اضغط للتحديد على الخريطة <i class="fas fa-chevron-left fa-xs"></i></span>
                `;
                    sidebarList.appendChild(card);

                    // 2. إضافة الماركر على الخريطة
                    const marker = L.marker([project.lat, project.lng])
                        .addTo(map)
                        .bindPopup(`
                        <div class="p-2">
                            <div class="popup-title">${esc(project.name)}</div>
                            <a href="/projects/${project.slug}" class="btn btn-sm btn-dark w-100 mt-2 text-white">تفاصيل المشروع</a>
                        </div>
                    `);

                    markers[index] = marker;

                    // 3. عند الضغط على الكارد في القائمة الجانبية
                    card.addEventListener('click', () => {
                        // تمييز الكارت المختار
                        document.querySelectorAll('.project-item-card').forEach(c => c.classList
                            .remove('active'));
                        card.classList.add('active');

                        // توجيه الخريطة وفتح البوب اب
                        map.flyTo([project.lat, project.lng], 15);
                        marker.openPopup();
                    });
                });

                // ملاءمة حدود الخريطة لتشمل كل النقاط
                const markerGroup = L.featureGroup(Object.values(markers));
                map.fitBounds(markerGroup.getBounds().pad(0.2));

                setTimeout(() => {
                    map.invalidateSize();
                }, 300);
            }
        });
    </script>
@endpush
