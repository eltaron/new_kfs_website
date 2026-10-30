@extends('layouts.app')
@section('title', 'استدامة للتدريب والتطوير')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/estidama-portal.css') }}?v={{ @filemtime(public_path('css/estidama-portal.css')) }}">
@endpush

@php
    $fallbackImg = asset('images/bg/estidama.jpeg');
    $heroTitle = trim(strip_tags(html_entity_decode((string) ($settings['estidama_hero_title'] ?? '')))) ?: 'استدامة للتدريب والتطوير';
    $capacity = (int) ($settings['estidama_capacity'] ?? 165) ?: 165;
    // YouTube intro (from the Estidama settings) — falls back to the bundled MP4 when not configured.
    $videoUrl = (string) ($settings['estidama_about_video_url'] ?? '');
    $youtubeId = preg_match('~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})~', $videoUrl, $m) ? $m[1] : null;
    $videoPoster = ! empty($settings['estidama_about_video_poster']) ? Storage::url($settings['estidama_about_video_poster']) : asset('estidama/bg.jpeg');
    $pillars = [
        ['رؤيتنا', 'fa-solid fa-eye', $settings['estidama_vision'] ?? null],
        ['رسالتنا', 'fa-solid fa-bullseye', $settings['estidama_mission'] ?? null],
        ['أهدافنا الاستراتيجية', 'fa-solid fa-chess-knight', $settings['estidama_strategic_goals'] ?? null],
    ];
    $pillars = array_filter($pillars, fn ($p) => filled(trim(strip_tags((string) $p[2]))));
    $gallery = [
        ['رؤيتنا للمستقبل', 'estidama/رؤيتنا للمستقبل.jpg'],
        ['رسالتنا', 'estidama/رسالتنا.jpg'],
        ['قيمنا الأساسية', 'estidama/القيم الاساسية التي تحركنا.jpg'],
        ['التصميم المكاني', 'estidama/التصميم المكاني.jpg'],
    ];
@endphp

@section('content')
    <main class="es-page estidama-page">

        {{-- 1. Hero --}}
        <x-kfs.page-hero :title="$heroTitle" kicker="منصة استدامة" icon="fa-solid fa-seedling"
            subtitle="مركز محافظة كفر الشيخ لتنمية رأس المال البشري — برامج تدريبية تفاعلية، تعلّم إلكتروني، وشهادات موثّقة برمز تحقق.">
            <x-slot:meta>
                <span><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> {{ number_format($programsCount) }} برنامج تدريبي</span>
                <span><i class="fa-solid fa-users" aria-hidden="true"></i> {{ number_format($traineesCount) }} متدرب</span>
                <span><i class="fa-solid fa-award" aria-hidden="true"></i> شهادات إلكترونية معتمدة</span>
            </x-slot:meta>
            <x-slot:actions>
                <a href="{{ route('estidama.programs') }}" class="btn-kfs-gold"><i class="fa-solid fa-grip" aria-hidden="true"></i> تصفح البرامج</a>
                @auth
                    <a href="{{ route('lms.dashboard') }}" class="btn-kfs-outline"><i class="fa-solid fa-book-open-reader" aria-hidden="true"></i> تعلّمي</a>
                @else
                    <a href="#es-about" class="btn-kfs-outline"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> تعرّف على المركز</a>
                @endauth
            </x-slot:actions>
        </x-kfs.page-hero>

        <div class="container">
            {{-- Quick certificate verification --}}
            <div class="es-card es-quick">
                <span class="es-ico" aria-hidden="true"><i class="fa-solid fa-qrcode"></i></span>
                <div class="es-quick__text">
                    <strong>تحقق من صحة شهادة تدريبية</strong>
                    <span>أدخل الرقم التسلسلي المطبوع على الشهادة الصادرة من مركز استدامة</span>
                </div>
                <form class="es-quick__form" action="{{ route('lms.certificates.verify') }}" method="GET" role="search">
                    <label for="esQuickSerial" class="visually-hidden">الرقم التسلسلي للشهادة</label>
                    <input id="esQuickSerial" type="text" name="serial" class="es-input es-mono" dir="ltr" maxlength="40"
                        pattern="[A-Za-z0-9\-]{6,40}" placeholder="KFS-EST-2026-XXXX" autocomplete="off" required>
                    <button class="es-btn" type="submit">تحقق <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                </form>
            </div>

            {{-- 2. Stats --}}
            <section class="es-section pt-4" aria-label="استدامة في أرقام">
                <div class="es-stats" data-stagger>
                    <div class="es-card es-stat">
                        <span class="es-ico" aria-hidden="true"><i class="fa-solid fa-graduation-cap"></i></span>
                        <div><b data-count="{{ (int) $programsCount }}">{{ number_format($programsCount) }}</b><span>برنامج تدريبي</span></div>
                    </div>
                    <div class="es-card es-stat">
                        <span class="es-ico" aria-hidden="true"><i class="fa-solid fa-user-graduate"></i></span>
                        <div><b data-count="{{ (int) $traineesCount }}">{{ number_format($traineesCount) }}</b><span>متدرب</span></div>
                    </div>
                    <div class="es-card es-stat">
                        <span class="es-ico es-ico--gold" aria-hidden="true"><i class="fa-solid fa-chair"></i></span>
                        <div><b data-count="{{ $capacity }}">{{ $capacity }}</b><span>الطاقة الاستيعابية</span></div>
                    </div>
                    <div class="es-card es-stat">
                        <span class="es-ico es-ico--ok" aria-hidden="true"><i class="fa-solid fa-laptop-code"></i></span>
                        <div><b>100%</b><span>تسجيل ومتابعة إلكترونية</span></div>
                    </div>
                </div>
            </section>

            {{-- 3. About --}}
            <section class="es-section" id="es-about" aria-labelledby="esAboutTitle">
                <div class="es-about">
                    <a class="es-about__media" href="{{ asset('estidama/الرؤية والرسالة والقيم.jpg') }}" data-lightbox="es-about"
                        data-caption="الرؤية والرسالة والقيم" data-reveal="start">
                        <img src="{{ asset('estidama/الرؤية والرسالة والقيم.jpg') }}" alt="الرؤية والرسالة والقيم لمركز استدامة" loading="lazy">
                    </a>
                    <div class="es-about__text" data-reveal>
                        <div class="es-head">
                            <div>
                                <span class="es-head__kicker">عن المركز</span>
                                <h2 class="es-head__title" id="esAboutTitle">مركز استدامة للتدريب والتطوير</h2>
                                <p class="es-head__lead">بنية تدريبية رقمية متكاملة لتطوير العنصر البشري في المحافظة وإقليم الدلتا.</p>
                            </div>
                        </div>
                        @if (filled(trim(strip_tags((string) ($settings['estidama_infrastructure'] ?? '')))))
                            <div class="es-features es-prose">
                                {!! clean($settings['estidama_infrastructure'], "cms") !!}
                            </div>
                        @endif
                        <div class="es-about__actions">
                            <a href="{{ route('estidama.programs') }}" class="es-btn"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> البرامج التدريبية</a>
                            <a href="{{ route('lms.certificates.verify') }}" class="es-btn es-btn--ghost"><i class="fa-solid fa-qrcode" aria-hidden="true"></i> التحقق من شهادة</a>
                        </div>
                    </div>
                </div>

                @if (count($pillars))
                    <div class="es-pillars" data-stagger>
                        @foreach ($pillars as [$label, $icon, $body])
                            <article class="es-card es-pillar">
                                <h3><span class="es-ico" aria-hidden="true"><i class="{{ $icon }}"></i></span>{{ $label }}</h3>
                                <div class="es-prose">{!! clean($body, 'cms') !!}</div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- 4. Programs --}}
        @if ($trainingPrograms->isNotEmpty())
            <section class="es-band" id="programs" aria-labelledby="esProgramsTitle">
                <div class="container">
                    <div class="es-head">
                        <div>
                            <span class="es-head__kicker">برامجنا</span>
                            <h2 class="es-head__title" id="esProgramsTitle">أحدث البرامج التدريبية</h2>
                            <p class="es-head__lead">اختر برنامجك التدريبي، واطلع على المحتوى والمواعيد، ثم سجّل إلكترونياً.</p>
                        </div>
                        <div class="es-head__tools">
                            @auth
                                <a href="{{ route('lms.dashboard') }}" class="es-btn es-btn--ghost es-btn--sm"><i class="fa-solid fa-book-open-reader" aria-hidden="true"></i> تعلّمي</a>
                            @endauth
                            <a href="{{ route('estidama.programs') }}" class="es-btn es-btn--ghost es-btn--sm">عرض كل البرامج <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                        </div>
                    </div>
                    <div class="es-grid" data-stagger>
                        @foreach ($trainingPrograms as $program)
                            @include('estidama.partials.program-card', ['program' => $program])
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <div class="container">
            {{-- 5. How it works --}}
            <section class="es-section" aria-labelledby="esHowTitle">
                <div class="es-head">
                    <div>
                        <span class="es-head__kicker">رحلة المتدرب</span>
                        <h2 class="es-head__title" id="esHowTitle">من التسجيل حتى الشهادة</h2>
                    </div>
                </div>
                <ol class="es-steps" data-stagger>
                    <li class="es-card es-step">
                        <span class="es-ico" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <h3>اختر البرنامج</h3>
                        <p>تصفح البرامج المتاحة واطلع على المحتوى والمواعيد والرسوم.</p>
                    </li>
                    <li class="es-card es-step">
                        <span class="es-ico" aria-hidden="true"><i class="fa-solid fa-file-pen"></i></span>
                        <h3>سجّل أو قدّم طلبك</h3>
                        <p>تسجيل مباشر، أو طلب التحاق يُراجع من إدارة المركز.</p>
                    </li>
                    <li class="es-card es-step">
                        <span class="es-ico" aria-hidden="true"><i class="fa-solid fa-laptop"></i></span>
                        <h3>تعلّم وتابع تقدمك</h3>
                        <p>دروس مرئية ومقروءة واختبارات قصيرة من مساحة «تعلّمي».</p>
                    </li>
                    <li class="es-card es-step">
                        <span class="es-ico es-ico--gold" aria-hidden="true"><i class="fa-solid fa-award"></i></span>
                        <h3>احصل على شهادتك</h3>
                        <p>شهادة إلكترونية برمز QR يمكن لأي جهة التحقق منها.</p>
                    </li>
                </ol>
            </section>

            {{-- 6. Gallery --}}
            <section class="es-section" aria-labelledby="esGalleryTitle">
                <div class="es-head">
                    <div>
                        <span class="es-head__kicker">استدامة</span>
                        <h2 class="es-head__title" id="esGalleryTitle">رؤيتنا، رسالتنا، وقيمنا الأساسية</h2>
                        <p class="es-head__lead">صور تحكي قصة استدامة للتدريب والتطوير — اضغط على أي صورة لعرضها بالحجم الكامل.</p>
                    </div>
                </div>
                <div class="es-gallery" data-stagger>
                    @foreach ($gallery as [$caption, $path])
                        <a class="es-shot" href="{{ asset($path) }}" data-lightbox="es-gallery" data-caption="{{ $caption }}">
                            <img src="{{ asset($path) }}" alt="{{ $caption }}" loading="lazy">
                            <span class="es-shot__cap">{{ $caption }} <i class="fa-solid fa-up-right-and-down-left-from-center" aria-hidden="true"></i></span>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- 7. Intro video --}}
            <section class="es-section" id="es-video" aria-labelledby="esVideoTitle">
                <div class="es-explain">
                    <div>
                        <span class="es-head__kicker">شاهد المركز</span>
                        <h2 id="esVideoTitle">جولة تعريفية في مركز استدامة</h2>
                        <p>تعرّف على القاعات الذكية ومعامل التكنولوجيا الرقمية ومنظومة التعلم الإلكتروني.</p>
                        <ul class="es-checks">
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>قاعات تدريبية مزودة بشاشات تفاعلية ذكية</span></li>
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>نظام بث وتسجيل للتدريب عن بُعد</span></li>
                            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>إدارة رقمية كاملة من التسجيل حتى إصدار الشهادة</span></li>
                        </ul>
                    </div>
                    <div>
                        @if ($youtubeId)
                            <div class="es-media">
                                <iframe src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}" title="فيديو تعريفي بمركز استدامة" loading="lazy"
                                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                            </div>
                        @else
                            <video class="es-video" controls preload="metadata" poster="{{ $videoPoster }}" playsinline aria-label="فيديو تعريفي بمركز استدامة">
                                <source src="{{ asset('estidama/video.mp4') }}" type="video/mp4">
                                متصفحك لا يدعم تشغيل الفيديو.
                            </video>
                        @endif
                    </div>
                </div>
            </section>

            {{-- 8. Events --}}
            @if ($events->isNotEmpty())
                <section class="es-section" id="events" aria-labelledby="esEventsTitle">
                    <div class="es-head">
                        <div>
                            <span class="es-head__kicker">أحداث</span>
                            <h2 class="es-head__title" id="esEventsTitle">أهم الأحداث بمركز استدامة</h2>
                        </div>
                        @if ($events->count() > 1)
                            <div class="es-nav">
                                <button type="button" class="es-events-prev" aria-label="الحدث السابق"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                                <button type="button" class="es-events-next" aria-label="الحدث التالي"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                            </div>
                        @endif
                    </div>
                    <div class="swiper est-events-slider es-events">
                        <div class="swiper-wrapper">
                            @foreach ($events as $event)
                                <div class="swiper-slide">
                                    <article class="es-card es-event">
                                        <div class="es-event__media">
                                            <img src="{{ Storage::url($event->image) }}" alt="{{ $event->title }}" loading="lazy"
                                                onerror="this.onerror=null;this.src='{{ $fallbackImg }}'">
                                        </div>
                                        <div class="es-event__body">
                                            <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                                            <h3>{{ $event->title }}</h3>
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                </section>
            @endif

            {{-- 9. Partners --}}
            @if (isset($partners) && $partners->isNotEmpty())
                <section class="es-section" aria-labelledby="esPartnersTitle">
                    <div class="es-head es-head--center">
                        <div>
                            <span class="es-head__kicker">شركاء النجاح</span>
                            <h2 class="es-head__title" id="esPartnersTitle">الاعتمادات والشركاء الاستراتيجيون</h2>
                        </div>
                    </div>
                    <div class="es-partners" data-stagger>
                        @foreach ($partners as $partner)
                            @php $tag = $partner->link ? 'a' : 'div'; @endphp
                            <{{ $tag }} class="es-card es-partner" @if ($partner->link) href="{{ $partner->link }}" target="_blank" rel="noopener" @endif>
                                @if ($partner->logo)
                                    <img src="{{ Storage::url($partner->logo) }}" alt="" loading="lazy" onerror="this.style.display='none'">
                                @endif
                                <span>{{ $partner->name }}</span>
                            </{{ $tag }}>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (document.querySelector('.est-events-slider') && window.Swiper) {
                new Swiper('.est-events-slider', {
                    loop: false,
                    slidesPerView: 1,
                    spaceBetween: 20,
                    navigation: { nextEl: '.es-events-next', prevEl: '.es-events-prev' },
                    pagination: { el: '.est-events-slider .swiper-pagination', clickable: true },
                    breakpoints: {
                        768: { slidesPerView: 2 },
                        992: { slidesPerView: 3 }
                    }
                });
            }
        });
    </script>
@endpush
