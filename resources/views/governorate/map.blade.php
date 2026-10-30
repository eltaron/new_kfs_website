@extends('layouts.app')
@section('title', 'الخريطة التفاعلية للمحافظة')

@push('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ asset('css') }}/map.css">
@endpush

@section('content')
    <x-kfs.page-hero size="compact" class="mb-0" title="الخريطة التفاعلية للمحافظة" kicker="دليل كفر الشيخ الذكي"
        icon="fa-solid fa-map-location-dot" subtitle="استكشف المعالم السياحية والفرص الاستثمارية والمشروعات القومية والخدمات على خريطة واحدة." />

    <div class="full-map-layout">
        <aside class="map-sidebar">
            <!-- الهيدر ثابت في الأعلى -->
            <div class="sidebar-header">
                <h4><i class="fas fa-map-marked-alt me-2 text-warning"></i> دليل كفر الشيخ الذكي</h4>
            </div>

            <!-- سيكشن التبويبات -->
            <div class="category-tabs-container">
                <div class="category-tabs custom-scrollbar" id="mapFilters">
                    <button class="cat-tab active" data-filter="all">عرض الكل</button>
                    <button class="cat-tab " data-filter="landmark">معالم سياحية</button>
                    <button class="cat-tab" data-filter="استثماري">فرص استثمارية</button>
                    <button class="cat-tab" data-filter="قومي">مشاريع قومية</button>
                    <button class="cat-tab" data-filter="صناعي">مناطق صناعية</button>
                    <button class="cat-tab" data-filter="guide">دليل الخدمات</button>
                </div>
            </div>

            <!-- القائمة هي الوحيدة التي تعمل سكرول -->
            <div class="items-list custom-scrollbar" id="itemsContainer">
                @foreach ($allData as $item)
                    <div class="map-item-card item-row" data-type="{{ $item['type'] }}" data-cat="{{ $item['category'] }}"
                        onclick="focusOnMarker('{{ $loop->index }}')">
                        @if ($item['image'])
                            <img src="{{ $item['image'] }}">
                        @else
                            <div class="no-img-icon"><i class="fas fa-map-pin"></i></div>
                        @endif
                        <div class="info">
                            <h6>{{ $item['name'] }}</h6>
                            <small>{{ $item['category'] }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </aside>

        <main class="map-viewer">
            <div id="fullInteractiveMap"></div>
        </main>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map;
        let markers = [];
        const pointsData = @json($allData);

        document.addEventListener('DOMContentLoaded', function() {
            // إنشاء الخريطة
            map = L.map('fullInteractiveMap', {
                zoomControl: false
            }).setView([31.1143, 30.9416], 11);

            // إضافة زر الزووم في مكان مميز
            L.control.zoom({
                position: 'topleft'
            }).addTo(map);

            // 1. طبقة الـ Satellite (Esri World Imagery)
            const satelliteLayer = L.tileLayer(
                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    attribution: 'Tiles &copy; Esri'
                }).addTo(map);

            // 2. طبقة الأسماء العربية الشفافة (Label Overlay)
            const labelsLayer = L.tileLayer(
                'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
                    pane: 'markerPane', // يجعلها تظهر فوق الستالايت
                    opacity: 0.8
                }).addTo(map);

            // رسم الـ Markers
            const markersGroup = L.featureGroup();

            pointsData.forEach((point, index) => {
                const marker = L.marker([point.lat, point.lng]).addTo(map);

                marker.bindPopup(`
                <div class="pop-content" style="text-align:right">
                    ${point.image ? `<img src="${point.image}" style="width:100%; border-radius:5px; margin-bottom:8px">` : ''}
                    <h6 class="fw-bold m-0">${point.name}</h6>
                    <p class="small text-muted mb-2">${point.category}</p>
                    <a href="${point.url}" class="btn btn-sm btn-dark w-100 py-1">عرض التفاصيل</a>
                </div>
            `);

                markers[index] = {
                    marker: marker,
                    type: point.type,
                    cat: point.category
                };
                markersGroup.addLayer(marker);
            });

            map.fitBounds(markersGroup.getBounds().pad(0.1));

            // نظام الفلترة
            document.querySelectorAll('.cat-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    // تفعيل التبويب بصرياً
                    document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove(
                        'active'));
                    this.classList.add('active');

                    const filter = this.dataset.filter;

                    // فلترة الخريطة
                    markers.forEach(obj => {
                        if (filter === 'all' || obj.type === filter || obj.cat === filter) {
                            obj.marker.addTo(map);
                        } else {
                            map.removeLayer(obj.marker);
                        }
                    });

                    // فلترة السايدبار
                    document.querySelectorAll('.item-row').forEach(row => {
                        const rowType = row.dataset.type;
                        const rowCat = row.dataset.cat;
                        if (filter === 'all' || rowType === filter || rowCat === filter) {
                            row.style.display = 'flex';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });
        });

        function focusOnMarker(index) {
            const item = markers[index];
            map.flyTo(item.marker.getLatLng(), 15, {
                duration: 1.5
            });
            item.marker.openPopup();
        }
    </script>
@endpush
