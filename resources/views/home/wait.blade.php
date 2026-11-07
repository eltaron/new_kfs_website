@extends('layouts.app')
@php
    // Optional context from a gated service (e.g. GisPortalController::comingSoon):
    // name, group, icon, back, backLabel, crumbs.
    $ctx = $context ?? [];
    $svcName = $ctx['name'] ?? null;
    $svcGroup = $ctx['group'] ?? null;
    $svcIcon = $ctx['icon'] ?? 'fa-solid fa-rocket';
    $backUrl = $ctx['back'] ?? url()->previous() ?: route('home');
    if ($backUrl === url()->current()) {
        $backUrl = route('home');
    }
    $backLabel = $ctx['backLabel'] ?? 'العودة للصفحة السابقة';
    $crumbs = $ctx['crumbs'] ?? [];
    $socials = collect([
        'social_facebook' => ['fa-brands fa-facebook-f', 'فيسبوك'],
        'social_twitter' => ['fa-brands fa-x-twitter', 'إكس'],
        'social_instagram' => ['fa-brands fa-instagram', 'إنستجرام'],
        'social_youtube' => ['fa-brands fa-youtube', 'يوتيوب'],
        'social_tiktok' => ['fa-brands fa-tiktok', 'تيك توك'],
    ])->map(fn ($meta, $key) => ['url' => trim((string) ($settings[$key] ?? '')), 'icon' => $meta[0], 'label' => $meta[1]])
      ->filter(fn ($s) => $s['url'] !== '' && $s['url'] !== '#');
    $next = collect([
        ['governorate.map', 'fa-solid fa-map-location-dot', 'الخريطة التفاعلية', 'استكشف المحافظة على خريطة واحدة'],
        ['services.index', 'fa-solid fa-hand-holding-hand', 'دليل الخدمات', 'الخدمات المتاحة الآن وشروطها'],
        ['contact.index', 'fa-solid fa-headset', 'تواصل معنا', 'لأي استفسار عن الخدمة'],
    ])->filter(fn ($n) => Route::has($n[0]));
@endphp
@section('title', ($svcName ? $svcName . ' — ' : '') . 'قريباً')
@section('description', 'هذه الخدمة قيد التجهيز وسيتم إطلاقها قريباً على الموقع الرسمي لمحافظة كفر الشيخ.')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/kfs-wait.css') }}?v={{ @filemtime(public_path('css/kfs-wait.css')) }}">
@endpush

@section('content')
    <main class="kwait">
        <section class="kwait__stage" aria-labelledby="kwaitTitle">
            {{-- Animated contour lines (decorative) --}}
            <svg class="kwait__contours" viewBox="0 0 1200 600" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
                <g fill="none" stroke="currentColor" stroke-width="1.2">
                    <path d="M-50 420 C 150 360, 300 470, 480 400 S 820 300, 980 360 S 1180 450, 1260 400" />
                    <path d="M-50 460 C 160 400, 310 510, 490 440 S 830 340, 990 400 S 1190 490, 1260 440" />
                    <path d="M-50 500 C 170 440, 320 550, 500 480 S 840 380, 1000 440 S 1200 530, 1260 480" />
                    <path d="M-50 140 C 120 200, 260 90, 420 150 S 760 240, 900 170 S 1120 80, 1260 130" />
                    <path d="M-50 100 C 130 160, 270 50, 430 110 S 770 200, 910 130 S 1130 40, 1260 90" />
                    <path d="M-50 60 C 140 120, 280 10, 440 70 S 780 160, 920 90 S 1140 0, 1260 50" />
                </g>
            </svg>

            <div class="container kwait__inner">
                <nav class="kwait__crumbs" aria-label="مسار التنقل">
                    <a href="{{ route('home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> الرئيسية</a>
                    @foreach ($crumbs as $label => $url)
                        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                        <a href="{{ $url }}">{{ $label }}</a>
                    @endforeach
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    <span aria-current="page">{{ $svcName ?: 'قريباً' }}</span>
                </nav>

                <div class="kwait__grid">
                    <div class="kwait__copy">
                        <span class="kwait__kicker"><span class="kwait__dot" aria-hidden="true"></span> قيد التجهيز</span>
                        <h1 class="kwait__title" id="kwaitTitle">
                            @if ($svcName)
                                <span class="kwait__title-sm">خدمة</span>
                                {{ $svcName }}
                            @else
                                خدمة جديدة في الطريق إليك
                            @endif
                        </h1>
                        @if ($svcGroup)
                            <span class="kwait__chip"><i class="{{ $svcIcon }}" aria-hidden="true"></i> {{ $svcGroup }}</span>
                        @endif
                        <p class="kwait__lead">
                            نعمل حالياً على تجهيز هذه الخدمة لتقديمها إلكترونياً بالكامل، من التقديم وحتى استلام النتيجة،
                            دون الحاجة لزيارة المقر. ترقّبوا إطلاقها <strong>قريباً</strong>.
                        </p>
                        <div class="kwait__actions">
                            <a href="{{ $backUrl }}" class="kwait__btn kwait__btn--gold">
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i><span>{{ $backLabel }}</span>
                            </a>
                            <a href="{{ route('home') }}" class="kwait__btn kwait__btn--ghost">
                                <i class="fa-solid fa-house" aria-hidden="true"></i><span>الرئيسية</span>
                            </a>
                        </div>
                    </div>

                    {{-- Radar / pin illustration --}}
                    <div class="kwait__art" aria-hidden="true">
                        <div class="kwait__radar">
                            <span class="kwait__ring kwait__ring--1"></span>
                            <span class="kwait__ring kwait__ring--2"></span>
                            <span class="kwait__ring kwait__ring--3"></span>
                            <span class="kwait__sweep"></span>
                            <span class="kwait__grid-lines"></span>
                            <span class="kwait__blip kwait__blip--a"></span>
                            <span class="kwait__blip kwait__blip--b"></span>
                            <span class="kwait__blip kwait__blip--c"></span>
                            <span class="kwait__pin"><i class="{{ $svcIcon }}"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            <svg class="kwait__wave" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                <path d="M0 40 C 240 80, 480 0, 720 30 S 1200 70, 1440 30 L1440 80 L0 80 Z" />
                <path class="kwait__wave-line" d="M0 40 C 240 80, 480 0, 720 30 S 1200 70, 1440 30" />
            </svg>
        </section>

        <section class="kwait__more" aria-labelledby="kwaitMoreTitle">
            <div class="container">
                <h2 class="kwait__more-title" id="kwaitMoreTitle">في الأثناء، يمكنك</h2>
                <div class="kwait__cards">
                    @foreach ($next as [$route, $icon, $title, $desc])
                        <a href="{{ route($route) }}" class="kwait__card">
                            <span class="kwait__card-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                            <span class="kwait__card-body"><strong>{{ $title }}</strong><small>{{ $desc }}</small></span>
                            <i class="fa-solid fa-arrow-left kwait__card-go" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>

                @if ($socials->isNotEmpty())
                    <div class="kwait__social">
                        <span>تابعونا لمعرفة موعد الإطلاق</span>
                        <div>
                            @foreach ($socials as $s)
                                <a href="{{ $s['url'] }}" target="_blank" rel="noopener" aria-label="{{ $s['label'] }}"><i class="{{ $s['icon'] }}" aria-hidden="true"></i></a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </main>
@endsection
