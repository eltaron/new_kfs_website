@extends('layouts.app')
@php
    $official = $governor?->official;
    $govName = trim((string) ($official->name ?? '')) ?: 'المهندس/ إبراهيم عبد القادر مكي محجوب';
    $imgPath = $official->image ?? ($settings['governor_page_image'] ?? null);
    $govImage = $imgPath ? (\Illuminate\Support\Str::startsWith($imgPath, ['http://', 'https://']) ? $imgPath : Storage::url($imgPath)) : null;
    $since = $governor?->start_year;
    $pageTitle = trim(strip_tags(clean($settings['governor_page_title'] ?? '', 'cms'))) ?: 'كلمة المحافظ';

    // The CMS text is mostly one <p> broken with <br>: turn breaks into real paragraphs.
    $raw = (string) ($settings['governor_page_content'] ?? '');
    $raw = preg_replace('#(<br\s*/?>\s*){1,}#i', '</p><p>', $raw);
    $message = trim(clean($raw, 'cms'));
    $message = preg_replace('#<p[^>]*>\s*(&nbsp;)?\s*</p>#u', '', $message);
    // Drop a closing line that only repeats the governor's name (the signature shows it).
    $squash = fn ($t) => preg_replace('/[\s\/\.\x{00A0}]+/u', '', html_entity_decode(strip_tags((string) $t), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if (preg_match('#(<(p|h[1-6])[^>]*>(?:(?!</\2>).)*</\2>)\s*$#su', $message, $m)
        && $squash($m[1]) !== '' && (str_contains($squash($govName), $squash($m[1])) || str_contains($squash($m[1]), $squash($govName)))) {
        $message = trim(substr($message, 0, -strlen($m[0])));
    }
    // Pull-quote: first sentence of the message.
    $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    $quote = preg_split('/(?<=[.،!؟])\s/u', $plain, 2)[0] ?? '';
    $quote = \Illuminate\Support\Str::limit(rtrim($quote, '،'), 170);
    $logo = !empty($settings['site_logo_header']) ? Storage::url($settings['site_logo_header']) : asset('logo.png');
@endphp
@section('title', $pageTitle . ' | محافظة كفر الشيخ')
@section('description', $quote ?: 'كلمة محافظ كفر الشيخ')
@if ($govImage)
    @section('og_image', \Illuminate\Support\Str::startsWith($govImage, 'http') ? $govImage : asset($govImage))
@endif

@push('css')
    <link rel="stylesheet" href="{{ asset('css/kfs-governor.css') }}?v={{ @filemtime(public_path('css/kfs-governor.css')) }}">
@endpush

@section('content')
    <main class="kgv">
        {{-- ===== Hero: portrait + title + pull quote ===== --}}
        <section class="kgv-hero" aria-labelledby="kgvTitle">
            <div class="container">
                <nav class="kgv-crumbs" aria-label="مسار التنقل">
                    <a href="{{ route('home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> الرئيسية</a>
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    <a href="{{ route('about') }}">عن المحافظة</a>
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    <span aria-current="page">{{ $pageTitle }}</span>
                </nav>

                <div class="kgv-hero__grid">
                    <div class="kgv-hero__copy">
                        <span class="kgv-kicker"><i class="fa-solid fa-feather-pointed" aria-hidden="true"></i> رسالة إلى أهالي كفر الشيخ</span>
                        <h1 class="kgv-hero__title" id="kgvTitle">{{ $pageTitle }}</h1>
                        @if ($quote)
                            <blockquote class="kgv-quote">
                                <i class="fa-solid fa-quote-right" aria-hidden="true"></i>
                                <p>{{ $quote }}</p>
                            </blockquote>
                        @endif
                        <a href="#kgvLetter" class="kgv-btn kgv-btn--gold">
                            <span>اقرأ الكلمة كاملة</span><i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                        </a>
                    </div>

                    <figure class="kgv-portrait">
                        <div class="kgv-portrait__frame">
                            @if ($govImage)
                                <img src="{{ $govImage }}" alt="{{ $govName }}">
                            @else
                                <span class="kgv-portrait__ph" aria-hidden="true"><i class="fa-solid fa-user-tie"></i></span>
                            @endif
                        </div>
                        <figcaption class="kgv-portrait__cap">
                            <strong>{{ $govName }}</strong>
                            <span>محافظ كفر الشيخ</span>
                            @if ($since)
                                <small><i class="fa-regular fa-calendar" aria-hidden="true"></i> منذ {{ $since }}</small>
                            @endif
                        </figcaption>
                    </figure>
                </div>
            </div>
            <svg class="kgv-hero__wave" viewBox="0 0 1440 70" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                <path d="M0 35 C 240 70, 480 0, 720 25 S 1200 60, 1440 25 L1440 70 L0 70 Z" />
                <path class="kgv-hero__wave-line" d="M0 35 C 240 70, 480 0, 720 25 S 1200 60, 1440 25" />
            </svg>
        </section>

        {{-- ===== The letter ===== --}}
        <section class="kgv-body">
            <div class="container">
                <article class="kgv-letter" id="kgvLetter" aria-label="نص الكلمة">
                    <header class="kgv-letter__head">
                        <img src="{{ $logo }}" alt="" class="kgv-letter__logo" width="56" height="56">
                        <div>
                            <span>محافظة كفر الشيخ</span>
                            <strong>ديوان عام المحافظة</strong>
                        </div>
                    </header>

                    <p class="kgv-letter__greet">بسم الله الرحمن الرحيم<br>أهالي محافظة كفر الشيخ الكرام،</p>

                    <div class="kgv-letter__text">
                        @if ($message !== '')
                            {!! $message !!}
                        @else
                            <p>سيتم نشر كلمة المحافظ قريباً.</p>
                        @endif
                    </div>

                    <footer class="kgv-sign">
                        <div class="kgv-sign__text">
                            <span>مع خالص التحية والتقدير،</span>
                            <strong>{{ $govName }}</strong>
                            <small>محافظ كفر الشيخ</small>
                        </div>
                        <span class="kgv-sign__seal" aria-hidden="true">
                            <img src="{{ $logo }}" alt="">
                        </span>
                    </footer>
                </article>

                {{-- Continue --}}
                <nav class="kgv-next" aria-label="روابط ذات صلة">
                    @foreach ([
                        ['officials.show', ['role' => 'governor'], 'fa-solid fa-id-card', 'السيرة الذاتية للمحافظ', 'المؤهلات والمسيرة المهنية'],
                        ['about', [], 'fa-solid fa-landmark-flag', 'عن المحافظة', 'التاريخ والرؤية والرموز'],
                        ['suggestions.create', [], 'fa-solid fa-lightbulb', 'شاركنا مقترحاتك', 'ملاحظاتكم تساعدنا على التطوير'],
                    ] as [$route, $params, $icon, $title, $desc])
                        @if (Route::has($route))
                            <a href="{{ route($route, $params) }}" class="kgv-next__card">
                                <span class="kgv-next__icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                                <span class="kgv-next__body"><strong>{{ $title }}</strong><small>{{ $desc }}</small></span>
                                <i class="fa-solid fa-arrow-left kgv-next__go" aria-hidden="true"></i>
                            </a>
                        @endif
                    @endforeach
                </nav>
            </div>
        </section>
    </main>
@endsection
