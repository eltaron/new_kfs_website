@extends('layouts.app')
@section('title', $landmark->name)

@push('css')
    <link rel="stylesheet" href="{{ asset('css/landmarks.css') }}?v={{ @filemtime(public_path('css/landmarks.css')) }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <style>
        :root {
            --primary-color: var(--kfs-primary);
            --primary-dark: var(--kfs-primary-hover);
            --secondary-color: var(--kfs-charcoal);
            --accent-color: var(--kfs-bg-alt);
        }

        /* معرض الصور */
        .gallery-section {
            margin: 3rem 0;
        }


        .project-gallery-slider {
            border-radius: 12px;
            overflow: hidden;
        }

        .project-gallery-slider .swiper-slide {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .project-gallery-slider img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .project-gallery-slider img:hover {
            transform: scale(1.05);
        }

        /* كيفية الوصول */
        .directions-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--r-lg);
            padding: clamp(20px, 3vw, 36px);
            box-shadow: var(--e-1);
            margin: 2.5rem 0;
        }

        .directions-card h4 {
            color: var(--secondary-color);
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.4rem;
        }

        .directions-card h4 svg {
            color: var(--primary-color);
        }

        .info-box {
            background: var(--accent-color);
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-inline-start: 4px solid var(--kfs-gold);
        }

        .info-box strong {
            color: var(--primary-color);
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .info-box p {
            margin: 0;
            color: #555;
            line-height: 1.8;
        }

        .map-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary-color);
            color: #fff;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            margin: 8px;
            font-weight: 600;
            border: 2px solid var(--primary-color);
        }

        .map-btn:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: #fff;
        }

        .map-btn.secondary {
            background: transparent;
            color: var(--primary-color);
        }

        .map-btn.secondary:hover {
            background: var(--primary-color);
            color: #fff;
        }

        .map-container {
            margin-top: 2rem;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .map-container iframe {
            width: 100%;
            height: 400px;
            border: none;
        }

        #directionsMapContainer {
            margin-top: 2rem;
        }

        #directionsMapLeaflet {
            height: 500px;
            border-radius: 12px;
            z-index: 1;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* Virtual Tour Section */
        .virtual-tour-section {
            margin: 3rem 0;
        }

        .map-iframe-wrapper-fullwidth {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .map-iframe-wrapper-fullwidth iframe {
            width: 100%;
            height: 450px;
            border: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .directions-card {
                padding: 2rem 1.5rem;
            }

            .booking-header h3 {
                font-size: 1.4rem;
            }

            .map-btn {
                display: flex;
                margin: 8px 0;
                justify-content: center;
            }

            #directionsMapLeaflet {
                height: 400px;
            }
        }
    </style>
@endpush

@section('content')
    <main class="main-content">
        {{-- Header --}}
        <x-kfs.page-hero :title="$landmark->name" kicker="معلم سياحي" icon="fa-solid fa-monument"
            :image="$landmark->thumbnail ? Storage::url($landmark->thumbnail) : asset('images/bg/terosim.jpg')"
            :crumbs="['السياحة والمعالم' => route('landmarks.index')]" />

        <div class="container pb-5">
            {{-- التفاصيل --}}
            <article class="rich-text-content kfs-panel">
                {!! clean($landmark->details, 'cms') !!}
            </article>

            {{-- معرض الصور --}}
            @if ($landmark->images->isNotEmpty())
                <div class="gallery-section">
                    <x-kfs.section-title title="معرض الصور" />
                    <div class="swiper project-gallery-slider">
                        <div class="swiper-wrapper">
                            @foreach ($landmark->images as $image)
                                <div class="swiper-slide">
                                    <a href="{{ Storage::url($image->path) }}" data-fslightbox="landmark-gallery">
                                        <img src="{{ Storage::url($image->path) }}"
                                            alt="{{ $image->caption ?? $landmark->name }}">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>
            @endif

            {{-- كيفية الوصول --}}
            @if($landmark->latitude && $landmark->longitude)
                <div class="directions-card">
                    <h4>
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm0-2.5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                        </svg>
                        كيفية الوصول للمعلم
                    </h4>

                    @if($landmark->address)
                        <div class="info-box">
                            <strong>📍 العنوان:</strong>
                            <p>{{ $landmark->address }}</p>
                        </div>
                    @endif

                    <div class="text-center my-4">
                        <button type="button" 
                                class="map-btn"
                                onclick="showDirectionsMap()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.654.689-1.782.886-3.112.752-1.234-.124-2.503-.523-3.388-.893V2.828zM7.5 1.916v.688c.588-.069 1.19-.093 1.788-.071.61.023 1.21.105 1.788.246V1.5a.5.5 0 0 1 .5-.5h.5a.5.5 0 0 1 .5.5v1.293c.578.141 1.178.223 1.788.246.598.022 1.2-.002 1.788-.071v-.688c-.588.069-1.19.093-1.788.071a12.5 12.5 0 0 0-1.788-.246V1.5a.5.5 0 0 1 .5-.5h.5a.5.5 0 0 1 .5.5v1.293c.578.141 1.178.223 1.788.246.598.022 1.2-.002 1.788-.071v-.688c-.588.069-1.19.093-1.788.071a12.5 12.5 0 0 0-1.788-.246V1.5a.5.5 0 0 1 .5-.5h.5a.5.5 0 0 1 .5.5v1.293c.578.141 1.178.223 1.788.246.598.022 1.2-.002 1.788-.071zM8 5.993c.588-.069 1.19-.093 1.788-.071.61.023 1.21.105 1.788.246V15a.5.5 0 0 1-.5.5h-.5a.5.5 0 0 1-.5-.5V6.164c-.578.141-1.178.223-1.788.246-.598.022-1.2-.002-1.788-.071V5.993z"/>
                            </svg>
                            اعرض خريطة الطريق من موقعي
                        </button>

                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $landmark->latitude }},{{ $landmark->longitude }}" 
                           target="_blank" 
                           class="map-btn secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                            </svg>
                            احسب المسار من موقعي الحالي
                        </a>
                    </div>

                    {{-- خريطة الاتجاهات --}}
                    <div id="directionsMapContainer" style="display: none;">
                        <div class="alert alert-info" role="alert" id="mapLoading">
                            <div class="d-flex align-items-center justify-content-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span>جاري تحديد موقعك وعرض الطريق...</span>
                            </div>
                        </div>
                        <div id="mapError" style="display: none;"></div>
                        <div id="directionsMapLeaflet" style="display: none;"></div>
                    </div>

                </div>
            @endif


        </div>
    </main>
@endsection
@push('scripts')
    {{-- FSLightbox (رابط مستقر) --}}
    <script src="https://cdn.jsdelivr.net/npm/fslightbox@3.4.1/index.js"></script>

    {{-- Swiper --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    {{-- Leaflet & Routing --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gallery Slider
            const gallerySlider = document.querySelector('.project-gallery-slider');
            if (gallerySlider) {
                new Swiper('.project-gallery-slider', {
                    loop: true,
                    spaceBetween: 20,
                    grabCursor: true,
                    slidesPerView: 1,
                    breakpoints: { 768: { slidesPerView: 2 }, 992: { slidesPerView: 3 } },
                    navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' }
                });
            }

            // FSLightbox Safe Init
            if (typeof refreshFsLightbox === 'function') {
                try { refreshFsLightbox(); } catch(e) {}
            }

            // Date Input Min
            const dateInput = document.getElementById('visit_date');
            if (dateInput) dateInput.min = new Date().toISOString().split('T')[0];

            // Form Submit Loading
            const form = document.getElementById('bookingForm');
            const btn = document.getElementById('submitBtn');
            form?.addEventListener('submit', () => {
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الإرسال...';
                btn.disabled = true;
            });
        });

        function showDirectionsMap() {
            const container = document.getElementById('directionsMapContainer');
            const loading = document.getElementById('mapLoading');
            const errorDiv = document.getElementById('mapError');
            const mapDiv = document.getElementById('directionsMapLeaflet');
            
            container.style.display = 'block';
            loading.style.display = 'block';
            errorDiv.style.display = 'none';
            mapDiv.style.display = 'none';

            if (!navigator.geolocation) {
                showError('المتصفح لا يدعم تحديد الموقع الجغرافي');
                return;
            }

            // تعريف الدوال بشكل صريح لمنع تعارض الـ Wrappers
            const onSuccess = (pos) => {
                const uLat = pos.coords.latitude, uLng = pos.coords.longitude;
                const dLat = {{ $landmark->latitude }}, dLng = {{ $landmark->longitude }};
                
                loading.style.display = 'none';
                mapDiv.style.display = 'block';

                const map = L.map('directionsMapLeaflet').setView([uLat, uLng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
                L.marker([uLat, uLng]).addTo(map).bindPopup('موقعك الحالي').openPopup();
                L.marker([dLat, dLng]).addTo(map).bindPopup('{{ $landmark->name }}');

                L.Routing.control({
                    waypoints: [L.latLng(uLat, uLng), L.latLng(dLat, dLng)],
                    routeWhileDragging: false,
                    // language: 'ar',
                    showAlternatives: true,
                    addWaypoints: false,
                    draggableWaypoints: false,
                    fitSelectedRoutes: true,
                    show: false,
                    lineOptions: { styles: [{ color: '#c9a226', opacity: 0.8, weight: 5 }] }
                }).addTo(map);
            };

            const onError = (err) => {
                let msg = 'فشل تحديد الموقع: ';
                if (err.code === 1) msg += 'تم رفض إذن الوصول للموقع.';
                else if (err.code === 2) msg += 'الموقع غير متاح حالياً.';
                else if (err.code === 3) msg += 'انتهت مهلة الانتظار.';
                else msg += 'خطأ غير معروف.';
                showError(msg);
            };

            try {
                // نمرر دالة خطأ صريحة حتى لو فاضية، عشان الـ Wrapper ميتكسرش
                navigator.geolocation.getCurrentPosition(onSuccess, onError || function(){}, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            } catch (e) {
                // Fallback لو الـ Wrapper قفل الـ API خالص
                loading.style.display = 'none';
                errorDiv.innerHTML = `
                    <div class="alert alert-warning">
                        تعذر تفعيل الخريطة التفاعلية بسبب تعارض مع إضافة المتصفح. 
                        يمكنك استخدام <a href="https://www.google.com/maps/dir/?api=1&destination={{ $landmark->latitude }},{{ $landmark->longitude }}" target="_blank" class="fw-bold">رابط خرائط جوجل المباشر</a> للوصول للمعلم.
                    </div>`;
                errorDiv.style.display = 'block';
            }
        }

        function showError(msg) {
            // document.getElementById('mapLoading').style.display = 'none';
            // const errDiv = document.getElementById('mapError');
            // errDiv.innerHTML = `<div class="alert alert-danger">⚠️ ${msg}</div>`;
            // errDiv.style.display = 'block';
        }
    </script>
@endpush