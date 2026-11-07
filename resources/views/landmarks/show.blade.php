@extends('layouts.app')
@section('title', trim($landmark->name) . ' | السياحة والمعالم')
@section('description', \Illuminate\Support\Str::limit(trim(html_entity_decode(strip_tags((string) $landmark->details), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 155) ?: 'معلم سياحي في محافظة كفر الشيخ')
@if ($landmark->thumbnail)
    @section('og_image', asset(Storage::url($landmark->thumbnail)))
@endif

@push('css')
    @if ($landmark->latitude && $landmark->longitude)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endif
    <link rel="stylesheet" href="{{ asset('css/kfs-landmark.css') }}?v={{ @filemtime(public_path('css/kfs-landmark.css')) }}">
@endpush

@section('content')
    @php
        $name = trim($landmark->name);
        $cover = $landmark->thumbnail ? Storage::url($landmark->thumbnail) : asset('images/bg/terosim.jpg');
        // Gallery = cover first (when set) + attached images, without duplicates.
        $photos = collect($landmark->thumbnail ? [['src' => $cover, 'caption' => $name]] : [])
            ->concat($landmark->images->map(fn ($img) => ['src' => Storage::url($img->path), 'caption' => $img->caption ?: $name]))
            ->unique('src')->values();
        $mosaic = $photos->take(5);
        $extra = $photos->count() - $mosaic->count();
        $hasCoords = $landmark->latitude && $landmark->longitude;
        $address = trim((string) $landmark->address);
        $directions = $hasCoords ? 'https://www.google.com/maps/dir/?api=1&destination=' . $landmark->latitude . ',' . $landmark->longitude : null;
        $bookUrl = \App\Support\EServices::visible('tour-bookings.create') ? route('tour-bookings.create') : null;
        $details = trim((string) $landmark->details);
    @endphp

    <main class="klm">
        <x-kfs.page-hero :title="$name" kicker="معلم سياحي" icon="fa-solid fa-monument" :image="$cover"
            :crumbs="['السياحة والمعالم' => route('landmarks.index')]">
            <x-slot:meta>
                @if ($address)
                    <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $address }}</span>
                @endif
                @if ($photos->count() > 1)
                    <span><i class="fa-solid fa-images" aria-hidden="true"></i> {{ $photos->count() }} صورة</span>
                @endif
            </x-slot:meta>
        </x-kfs.page-hero>

        <div class="container klm__wrap">
            <div class="klm__layout">
                {{-- ===== Main column ===== --}}
                <div class="klm__main">
                    {{-- Gallery mosaic --}}
                    @if ($photos->isNotEmpty())
                        <section class="klm-gallery klm-gallery--n{{ min($mosaic->count(), 5) }}" aria-label="معرض الصور">
                            @foreach ($mosaic as $i => $photo)
                                <a href="{{ $photo['src'] }}" class="klm-gallery__item" data-fslightbox="landmark"
                                    data-caption="{{ $photo['caption'] }}" aria-label="عرض الصورة {{ $i + 1 }} من {{ $photos->count() }}">
                                    <img src="{{ $photo['src'] }}" alt="{{ $photo['caption'] }}" @if ($i > 0) loading="lazy" @endif>
                                    @if ($loop->last && $extra > 0)
                                        <span class="klm-gallery__more"><i class="fa-solid fa-images" aria-hidden="true"></i> +{{ $extra }} صورة</span>
                                    @endif
                                </a>
                            @endforeach
                            {{-- Remaining photos: part of the lightbox set, not shown in the mosaic --}}
                            @foreach ($photos->slice($mosaic->count()) as $photo)
                                <a href="{{ $photo['src'] }}" data-fslightbox="landmark" data-caption="{{ $photo['caption'] }}" hidden tabindex="-1" aria-hidden="true"></a>
                            @endforeach
                            @if ($photos->count() > 1)
                                <button type="button" class="klm-gallery__all" data-open-gallery>
                                    <i class="fa-solid fa-grip" aria-hidden="true"></i> عرض كل الصور
                                </button>
                            @endif
                        </section>
                    @endif

                    {{-- About --}}
                    <section class="klm-card klm-about" aria-labelledby="klmAboutTitle">
                        <h2 class="klm-card__title" id="klmAboutTitle">
                            <span class="klm-card__icon" aria-hidden="true"><i class="fa-solid fa-book-open"></i></span>
                            عن المعلم
                        </h2>
                        @if ($details !== '')
                            <div class="klm-prose">{!! clean($details, 'cms') !!}</div>
                        @else
                            <p class="klm-muted">لا يوجد وصف لهذا المعلم حتى الآن.</p>
                        @endif
                    </section>

                    {{-- Location map --}}
                    @if ($hasCoords)
                        <section class="klm-card klm-location" aria-labelledby="klmMapTitle">
                            <div class="klm-location__head">
                                <h2 class="klm-card__title" id="klmMapTitle">
                                    <span class="klm-card__icon" aria-hidden="true"><i class="fa-solid fa-map-location-dot"></i></span>
                                    الموقع على الخريطة
                                </h2>
                                @if (Route::has('governorate.map'))
                                    <a href="{{ route('governorate.map') }}" class="klm-link">الخريطة التفاعلية <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                                @endif
                            </div>
                            <div class="klm-map" id="klmMap" role="region" aria-label="خريطة موقع {{ $name }}"
                                data-lat="{{ $landmark->latitude }}" data-lng="{{ $landmark->longitude }}" data-name="{{ $name }}"></div>
                        </section>
                    @endif
                </div>

                {{-- ===== Aside ===== --}}
                <aside class="klm__aside" aria-label="معلومات الزيارة">
                    <div class="klm-info">
                        <h2 class="klm-info__title">معلومات الزيارة</h2>
                        <dl class="klm-info__list">
                            <div>
                                <dt><i class="fa-solid fa-tag" aria-hidden="true"></i> التصنيف</dt>
                                <dd>معلم سياحي</dd>
                            </div>
                            @if ($address)
                                <div>
                                    <dt><i class="fa-solid fa-location-dot" aria-hidden="true"></i> العنوان</dt>
                                    <dd>{{ $address }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt><i class="fa-solid fa-map-pin" aria-hidden="true"></i> المحافظة</dt>
                                <dd>كفر الشيخ</dd>
                            </div>
                            @if ($photos->count())
                                <div>
                                    <dt><i class="fa-solid fa-camera" aria-hidden="true"></i> الصور</dt>
                                    <dd>{{ $photos->count() }}</dd>
                                </div>
                            @endif
                        </dl>

                        <div class="klm-info__actions">
                            @if ($directions)
                                <a href="{{ $directions }}" target="_blank" rel="noopener" class="klm-btn klm-btn--primary">
                                    <i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i><span>الاتجاهات من موقعي</span>
                                </a>
                            @endif
                            @if ($bookUrl)
                                <a href="{{ $bookUrl }}" class="klm-btn klm-btn--gold">
                                    <i class="fa-solid fa-route" aria-hidden="true"></i><span>احجز رحلة</span>
                                </a>
                            @endif
                            <button type="button" class="klm-btn" id="klmShare" data-title="{{ $name }}">
                                <i class="fa-solid fa-share-nodes" aria-hidden="true"></i><span>مشاركة</span>
                            </button>
                        </div>
                        <p class="klm-info__toast" id="klmShareToast" role="status" hidden>تم نسخ الرابط</p>
                    </div>

                    <a href="{{ route('landmarks.index') }}" class="klm-back">
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i> كل المعالم السياحية
                    </a>
                </aside>
            </div>

            {{-- ===== More landmarks ===== --}}
            @if ($related->isNotEmpty())
                <section class="klm-related" aria-labelledby="klmRelatedTitle">
                    <x-kfs.section-title id="klmRelatedTitle" title="معالم أخرى تستحق الزيارة" :link="route('landmarks.index')" linkText="كل المعالم" />
                    <div class="klm-related__grid">
                        @foreach ($related as $item)
                            <a href="{{ route('landmarks.show', $item->id) }}" class="klm-related__card">
                                <span class="klm-related__img">
                                    <img src="{{ $item->thumbnail ? Storage::url($item->thumbnail) : asset('images/bg/terosim.jpg') }}" alt="" loading="lazy">
                                </span>
                                <span class="klm-related__body">
                                    <strong>{{ trim($item->name) }}</strong>
                                    @if (trim((string) $item->address))
                                        <small><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ trim($item->address) }}</small>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </main>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fslightbox@3.4.1/index.js"></script>
    @if ($hasCoords)
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endif
    <script>
    (function () {
        // Gallery: "عرض كل الصور" opens the lightbox at the first photo.
        document.querySelector('[data-open-gallery]')?.addEventListener('click', () => {
            if (window.fsLightboxInstances && fsLightboxInstances.landmark) fsLightboxInstances.landmark.open(0);
            else document.querySelector('.klm-gallery__item')?.click();
        });

        // Location map (static view, OpenStreetMap tiles — no API key).
        const mapEl = document.getElementById('klmMap');
        if (mapEl && window.L) {
            const ll = [parseFloat(mapEl.dataset.lat), parseFloat(mapEl.dataset.lng)];
            const map = L.map(mapEl, { scrollWheelZoom: false, zoomControl: true }).setView(ll, 14);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
            }).addTo(map);
            const pin = L.divIcon({ className: 'klm-pin-wrap', html: '<span class="klm-pin"><i class="fa-solid fa-monument" aria-hidden="true"></i></span>', iconSize: [40, 48], iconAnchor: [20, 46] });
            const marker = L.marker(ll, { icon: pin, title: mapEl.dataset.name }).addTo(map);
            const label = document.createElement('strong');
            label.textContent = mapEl.dataset.name;
            marker.bindTooltip(label, { direction: 'top', offset: [0, -44], permanent: true, className: 'klm-tip' });
        }

        // Share: native sheet when available, otherwise copy the link.
        const share = document.getElementById('klmShare');
        const toast = document.getElementById('klmShareToast');
        share?.addEventListener('click', async () => {
            const data = { title: share.dataset.title, url: location.href };
            try {
                if (navigator.share) { await navigator.share(data); return; }
                await navigator.clipboard.writeText(location.href);
                toast.hidden = false;
                setTimeout(() => { toast.hidden = true; }, 2200);
            } catch (e) { /* user cancelled */ }
        });
    })();
    </script>
@endpush
