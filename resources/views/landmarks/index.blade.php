@extends('layouts.app')
@section('title', 'اكتشف المعالم السياحية')

@push('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ asset('css/landmarks-show.css') }}?v={{ @filemtime(public_path('css/landmarks-show.css')) }}">
@endpush

@section('content')
    {{-- تحضير بيانات الخريطة بناءً على التعديلات الجديدة --}}
    @php
        $mapData = $landmarks
            ->filter(fn($l) => $l->latitude && $l->longitude)
            ->map(
                fn($l) => [
                    'id' => $l->id,
                    'name' => $l->name,
                    'lat' => (float) $l->latitude,
                    'lng' => (float) $l->longitude,
                    'thumb' => Storage::url($l->thumbnail),
                ],
            )
            ->values();
    @endphp
    <main class="main-content pb-5">
        {{-- Page Header --}}
        <x-kfs.page-hero title="الخريطة السياحية التفاعلية" subtitle="دليلك لاستكشاف المعالم التاريخية والطبيعية في قلب الدلتا" kicker="كنوز وتاريخ" icon="fa-solid fa-monument" :image="asset('images/bg/terosim.jpg')" />

        <div class="container">
            {{-- سكشن الخريطة والسياحة التفاعلي --}}
            @if ($mapData->isNotEmpty())
            <x-kfs.section-title title="المعالم على الخريطة" subtitle="اختر معلماً من القائمة لعرض موقعه على الخريطة." />
            <div class="tourism-interactive-grid mb-5">
                {{-- قائمة المعالم السياحية (الجانب الأيمن) --}}
                <div class="map-sidebar-tourism">
                    <div class="sidebar-top">
                        <h2 class="h6 m-0"><i class="fa-solid fa-list-ul ms-1" aria-hidden="true"></i> جميع المعالم</h2>
                    </div>
                    <div class="list-container custom-scrollbar" id="landmarksSideList">
                        {{-- يتم تعبئتها بواسطة Javascript --}}
                    </div>
                </div>

                {{-- الخريطة التفاعلية (الجانب الأيسر) --}}
                <div class="map-canvas-tourism">
                    <div id="interactiveTourismMap"></div>
                    <div class="map-loader-overlay">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">جارٍ تحميل الخريطة</span></div>
                    </div>
                </div>
            </div>
            @endif

            {{-- الأرشيف الشبكي بالأسفل --}}
            <x-kfs.section-title title="معالم المحافظة" subtitle="مواقع تاريخية وطبيعية تستحق الزيارة في قلب الدلتا." />
            @if ($landmarks->isNotEmpty())
                <div class="kfs-grid kfs-grid--3" data-stagger>
                    @foreach ($landmarks as $landmark)
                        <a href="{{ route('landmarks.show', $landmark->id) }}" class="kfs-tour kfs-tour--wide">
                            <img src="{{ $landmark->thumbnail ? Storage::url($landmark->thumbnail) : asset('images/kfs.png') }}" alt="" loading="lazy">
                            <span class="kfs-tour__caption">
                                <span class="kfs-tour__kicker"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> كفر الشيخ</span>
                                <span class="kfs-tour__title">{{ $landmark->name }}</span>
                                <span class="kfs-tour__more">التفاصيل الكاملة <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
                            </span>
                        </a>
                    @endforeach
                </div>

                {{-- الترقيم --}}
                <div class="pagination-wrapper mt-5">
                    {{ $landmarks->links() }}
                </div>
            @else
                <x-kfs.empty-state icon="map" title="لا توجد معالم منشورة حالياً" />
            @endif
        </div>
    </main>


@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapData = @json($mapData);
            const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const mapContainer = document.getElementById('interactiveTourismMap');
            const landmarkUrl = @json(route('landmarks.show', '__ID__'));
            const listWrapper = document.getElementById('landmarksSideList');

            if (mapContainer && mapData.length > 0) {
                // إعداد الخريطة
                const map = L.map(mapContainer, {
                        scrollWheelZoom: false
                    })
                    .setView([31.1143, 30.9416], 11);

                // طبقة شوارع بالعربي احترافية
                L.tileLayer(
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
                        attribution: '© Esri Tourism Map'
                    }).addTo(map);

                const markers = L.featureGroup();

                mapData.forEach((item) => {
                    // 1. إنشاء عنصر القائمة اليمين
                    const card = document.createElement('button');
                    card.type = 'button';
                    card.className = 'landmark-side-item';
                    card.style.cssText = 'width:100%;text-align:start;background:none;font:inherit;';
                    card.innerHTML = `
                        <img src="${esc(item.thumb)}" class="item-thumb" alt="">
                        <div class="item-info">
                            <h6>${esc(item.name)}</h6>
                            <small class="text-muted"><i class="fas fa-location-dot me-1"></i> اضغط للمعاينة</small>
                        </div>
                    `;
                    listWrapper.appendChild(card);

                    // 2. إنشاء الماركر
                    const marker = L.marker([item.lat, item.lng]).addTo(markers);
                    marker.bindPopup(`
                        <div style="min-width: 150px">
                            <img src="${esc(item.thumb)}" alt="" style="width:100%; border-radius:8px; margin-bottom:5px">
                            <strong style="color:var(--kfs-charcoal)">${esc(item.name)}</strong>
                            <hr class="my-1">
                            <a href="${esc(landmarkUrl.replace('__ID__', item.id))}" style="color:var(--kfs-primary); font-weight:700; font-size:0.85rem">عرض التفاصيل ←</a>
                        </div>
                    `);

                    // 3. الربط عند الضغط
                    card.addEventListener('click', () => {
                        document.querySelectorAll('.landmark-side-item').forEach(el => el.classList
                            .remove('active'));
                        card.classList.add('active');
                        map.flyTo([item.lat, item.lng], 14, {
                            duration: 1.5
                        });
                        marker.openPopup();
                    });
                });

                markers.addTo(map);
                map.fitBounds(markers.getBounds().pad(0.2));

                // إخفاء الـ Loading بعد جاهزية الخريطة
                setTimeout(() => {
                    document.querySelector('.map-loader-overlay').style.display = 'none';
                    map.invalidateSize();
                }, 400);
            }
        });
    </script>
@endpush
