@extends('layouts.app')
@section('title', trim($project->name) . ' | مشروعات المحافظة')
@section('description', \Illuminate\Support\Str::limit(trim(html_entity_decode(strip_tags((string) $project->description), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 155) ?: 'مشروعات محافظة كفر الشيخ')
@if ($project->thumbnail)
    @section('og_image', asset(Storage::url($project->thumbnail)))
@endif

@php
    $hasCoords = $project->latitude && $project->longitude;
@endphp

@push('css')
    @if ($hasCoords)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endif
    {{-- Shared detail-page layout (gallery · cards · aside · related) with the landmark page --}}
    <link rel="stylesheet" href="{{ asset('css/kfs-landmark.css') }}?v={{ @filemtime(public_path('css/kfs-landmark.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/kfs-project.css') }}?v={{ @filemtime(public_path('css/kfs-project.css')) }}">
@endpush

@section('content')
    @php
        $name = trim($project->name);
        $category = trim((string) $project->category);
        $kinds = [
            'قومي' => ['label' => 'مشروع قومي', 'icon' => 'fa-solid fa-flag', 'tone' => 'primary'],
            'صناعي' => ['label' => 'منطقة صناعية', 'icon' => 'fa-solid fa-industry', 'tone' => 'olive'],
            'حياة كريمة' => ['label' => 'مبادرة حياة كريمة', 'icon' => 'fa-solid fa-hand-holding-heart', 'tone' => 'gold'],
            'استثماري' => ['label' => 'فرصة استثمارية', 'icon' => 'fa-solid fa-chart-line', 'tone' => 'bronze'],
        ];
        $kind = $kinds[$category] ?? ['label' => $category ?: 'مشروع', 'icon' => 'fa-solid fa-diagram-project', 'tone' => 'primary'];
        $isInvestment = $category === 'استثماري';

        $cover = $project->thumbnail ? Storage::url($project->thumbnail) : asset('images/bg/projects.jpg');
        $photos = collect($project->thumbnail ? [['src' => $cover, 'caption' => $name]] : [])
            ->concat($project->images->map(fn ($img) => ['src' => Storage::url($img->path), 'caption' => $img->caption ?: $name]))
            ->unique('src')->values();
        $mosaic = $photos->take(5);
        $extra = $photos->count() - $mosaic->count();

        $description = trim((string) $project->description);
        $isLong = mb_strlen(strip_tags($description)) > 900;
        $iframe = trim((string) $project->iframe);
        $directions = $hasCoords ? 'https://www.google.com/maps/dir/?api=1&destination=' . $project->latitude . ',' . $project->longitude : null;
        $program = $project->investment;
        $listUrl = match ($category) {
            'حياة كريمة' => Route::has('projects.hay-karima') ? route('projects.hay-karima') : route('projects.index'),
            'استثماري', 'صناعي' => $program ? route('investments.show', $program) : (Route::has('investments.index') ? route('investments.index') : route('projects.index')),
            default => route('projects.index'),
        };
        $listLabel = match ($category) {
            'حياة كريمة' => 'كل مشروعات حياة كريمة',
            'استثماري', 'صناعي' => $program ? 'كل ' . trim($program->title) : 'الاستثمار',
            default => 'كل المشروعات القومية',
        };
    @endphp

    <main class="klm kpj kpj--{{ $kind['tone'] }}">
        <x-kfs.page-hero :title="$name" :kicker="$kind['label']" :icon="$kind['icon']" :image="$cover"
            :crumbs="['المشروعات' => route('projects.index')]">
            <x-slot:meta>
                <span><i class="{{ $kind['icon'] }}" aria-hidden="true"></i> {{ $kind['label'] }}</span>
                @if ($program && ! in_array(trim($program->title), [$kind['label'], $category], true))
                    <span><i class="fa-solid fa-layer-group" aria-hidden="true"></i> {{ trim($program->title) }}</span>
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
                    @if ($photos->isNotEmpty())
                        <section class="klm-gallery klm-gallery--n{{ min($mosaic->count(), 5) }}" aria-label="معرض الصور">
                            @foreach ($mosaic as $i => $photo)
                                <a href="{{ $photo['src'] }}" class="klm-gallery__item" data-fslightbox="project"
                                    data-caption="{{ $photo['caption'] }}" aria-label="عرض الصورة {{ $i + 1 }} من {{ $photos->count() }}">
                                    <img src="{{ $photo['src'] }}" alt="{{ $photo['caption'] }}" @if ($i > 0) loading="lazy" @endif>
                                    @if ($loop->last && $extra > 0)
                                        <span class="klm-gallery__more"><i class="fa-solid fa-images" aria-hidden="true"></i> +{{ $extra }} صورة</span>
                                    @endif
                                </a>
                            @endforeach
                            @foreach ($photos->slice($mosaic->count()) as $photo)
                                <a href="{{ $photo['src'] }}" data-fslightbox="project" data-caption="{{ $photo['caption'] }}" hidden tabindex="-1" aria-hidden="true"></a>
                            @endforeach
                            @if ($photos->count() > 1)
                                <button type="button" class="klm-gallery__all" data-open-gallery>
                                    <i class="fa-solid fa-grip" aria-hidden="true"></i> عرض كل الصور
                                </button>
                            @endif
                        </section>
                    @endif

                    <section class="klm-card" aria-labelledby="kpjAboutTitle">
                        <h2 class="klm-card__title" id="kpjAboutTitle">
                            <span class="klm-card__icon kpj-icon" aria-hidden="true"><i class="fa-solid fa-circle-info"></i></span>
                            عن المشروع
                        </h2>
                        @if ($description !== '')
                            <div class="kpj-readmore {{ $isLong ? 'is-collapsed' : '' }}" id="kpjAbout">
                                <div class="klm-prose">{!! clean($description, 'cms') !!}</div>
                            </div>
                            @if ($isLong)
                                <button type="button" class="kpj-readmore__btn" aria-controls="kpjAbout" aria-expanded="false" data-more="اقرأ المزيد" data-less="عرض أقل">
                                    <span>اقرأ المزيد</span> <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                </button>
                            @endif
                        @else
                            <p class="klm-muted">سيتم إضافة تفاصيل هذا المشروع قريبًا.</p>
                        @endif
                    </section>

                    @if ($hasCoords || $iframe !== '')
                        <section class="klm-card" aria-labelledby="kpjMapTitle">
                            <div class="klm-location__head">
                                <h2 class="klm-card__title" id="kpjMapTitle">
                                    <span class="klm-card__icon kpj-icon" aria-hidden="true"><i class="fa-solid fa-map-location-dot"></i></span>
                                    موقع المشروع
                                </h2>
                                @if ($hasCoords && Route::has('governorate.map'))
                                    <a href="{{ route('governorate.map') }}" class="klm-link">الخريطة التفاعلية <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                                @endif
                            </div>
                            @if ($hasCoords)
                                <div class="klm-map" id="kpjMap" role="region" aria-label="خريطة موقع {{ $name }}"
                                    data-lat="{{ $project->latitude }}" data-lng="{{ $project->longitude }}" data-name="{{ $name }}" data-icon="{{ $kind['icon'] }}"></div>
                            @else
                                <div class="kpj-embed">{!! clean(preg_replace('/(width|height)="[^"]*"/', '', $iframe), 'cms') !!}</div>
                            @endif
                        </section>
                    @endif
                </div>

                {{-- ===== Aside ===== --}}
                <aside class="klm__aside" aria-label="بطاقة المشروع">
                    <div class="klm-info kpj-info">
                        <span class="kpj-badge"><i class="{{ $kind['icon'] }}" aria-hidden="true"></i> {{ $kind['label'] }}</span>
                        <h2 class="klm-info__title">بطاقة المشروع</h2>
                        <dl class="klm-info__list">
                            <div>
                                <dt><i class="fa-solid fa-tag" aria-hidden="true"></i> التصنيف</dt>
                                <dd>{{ $kind['label'] }}</dd>
                            </div>
                            @if ($program)
                                <div>
                                    <dt><i class="fa-solid fa-layer-group" aria-hidden="true"></i> المحور</dt>
                                    <dd><a href="{{ route('investments.show', $program) }}">{{ trim($program->title) }}</a></dd>
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
                                    <i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i><span>الاتجاهات إلى الموقع</span>
                                </a>
                            @endif
                            <button type="button" class="klm-btn" id="klmShare" data-title="{{ $name }}">
                                <i class="fa-solid fa-share-nodes" aria-hidden="true"></i><span>مشاركة</span>
                            </button>
                        </div>
                        <p class="klm-info__toast" id="klmShareToast" role="status" hidden>تم نسخ الرابط</p>
                    </div>

                    @if ($isInvestment && Route::has('investment.contact'))
                        {{-- Investment opportunities only --}}
                        <div class="kpj-cta">
                            <span class="kpj-cta__icon" aria-hidden="true"><i class="fa-solid fa-headset"></i></span>
                            <p class="kpj-cta__text">في حالة الرغبة في التقدم لأي فرصة أو وجود أي استفسارات</p>
                            <a href="{{ route('investment.contact') }}" class="klm-btn klm-btn--gold">
                                <span>تواصل معنا من هنا</span><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endif

                    <a href="{{ $listUrl }}" class="klm-back">
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i> {{ $listLabel }}
                    </a>
                </aside>
            </div>

            @if ($related->isNotEmpty())
                <section class="klm-related" aria-labelledby="kpjRelatedTitle">
                    <x-kfs.section-title id="kpjRelatedTitle" title="مشروعات أخرى" :link="$listUrl" :linkText="$listLabel" />
                    <div class="klm-related__grid">
                        @foreach ($related as $item)
                            @php $k = $kinds[trim((string) $item->category)] ?? ['label' => $item->category ?: 'مشروع', 'icon' => 'fa-solid fa-diagram-project']; @endphp
                            <a href="{{ route('projects.show', $item->slug) }}" class="klm-related__card">
                                <span class="klm-related__img">
                                    <img src="{{ $item->thumbnail ? Storage::url($item->thumbnail) : asset('images/bg/projects.jpg') }}" alt="" loading="lazy">
                                </span>
                                <span class="klm-related__body">
                                    <small class="kpj-related__kind"><i class="{{ $k['icon'] }}" aria-hidden="true"></i> {{ $k['label'] }}</small>
                                    <strong>{{ \Illuminate\Support\Str::limit(trim($item->name), 90) }}</strong>
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
        document.querySelector('[data-open-gallery]')?.addEventListener('click', () => {
            if (window.fsLightboxInstances && fsLightboxInstances.project) fsLightboxInstances.project.open(0);
            else document.querySelector('.klm-gallery__item')?.click();
        });

        // Long descriptions: collapse with «اقرأ المزيد».
        const more = document.querySelector('.kpj-readmore__btn');
        more?.addEventListener('click', () => {
            const box = document.getElementById('kpjAbout');
            const open = box.classList.toggle('is-collapsed') === false;
            more.setAttribute('aria-expanded', open ? 'true' : 'false');
            more.querySelector('span').textContent = open ? more.dataset.less : more.dataset.more;
            if (!open) box.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        const mapEl = document.getElementById('kpjMap');
        if (mapEl && window.L) {
            const ll = [parseFloat(mapEl.dataset.lat), parseFloat(mapEl.dataset.lng)];
            const map = L.map(mapEl, { scrollWheelZoom: false }).setView(ll, 14);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
            }).addTo(map);
            const i = document.createElement('i');
            i.className = mapEl.dataset.icon;
            i.setAttribute('aria-hidden', 'true');
            const pin = L.divIcon({ className: 'klm-pin-wrap', html: '<span class="klm-pin kpj-pin">' + i.outerHTML + '</span>', iconSize: [40, 48], iconAnchor: [20, 46] });
            const marker = L.marker(ll, { icon: pin, title: mapEl.dataset.name }).addTo(map);
            const label = document.createElement('strong');
            label.textContent = mapEl.dataset.name.length > 40 ? mapEl.dataset.name.slice(0, 40) + '…' : mapEl.dataset.name;
            marker.bindTooltip(label, { direction: 'top', offset: [0, -44], permanent: true, className: 'klm-tip' });
        }

        const share = document.getElementById('klmShare');
        const toast = document.getElementById('klmShareToast');
        share?.addEventListener('click', async () => {
            try {
                if (navigator.share) { await navigator.share({ title: share.dataset.title, url: location.href }); return; }
                await navigator.clipboard.writeText(location.href);
                toast.hidden = false;
                setTimeout(() => { toast.hidden = true; }, 2200);
            } catch (e) { /* cancelled */ }
        });
    })();
    </script>
@endpush
