@extends('layouts.app')
@push('css')
    @if ($guideCategories->isNotEmpty())
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endif
@endpush
@section('content')
    @php
        $kfsRoute = fn (string $name, array $params = []) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : null;
        $kfsImg = fn ($path, $fallback = 'images/kfs.png') => $path ? Storage::url($path) : asset($fallback);
        $kfsLogoUrl = !empty($settings['site_logo_header']) ? Storage::url($settings['site_logo_header']) : asset('logo.png');
        $kfsPlain = fn ($html, $limit = 140) => \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), $limit);
        $kfsText = fn ($key, $default) => trim(strip_tags((string) ($settings[$key] ?? ''))) ?: $default;

        // E-services quick links (static; every entry is guarded by Route::has).
        $kfsQuick = collect([
            ['services.index', 'fa-solid fa-hand-holding-hand', 'دليل الخدمات', 'كل الخدمات الحكومية وشروطها ومستنداتها', null],
            ['requests.index', 'fa-solid fa-file-pen', 'الشكاوى والبلاغات', 'قدّم شكواك أو بلاغك وتابع حالتها', null],
            ['gis.index', 'fa-solid fa-map-location-dot', 'الخدمات الجغرافية', 'خدمات المساحة والمواقع والتراخيص', null],
            ['site-validity.landing', 'fa-solid fa-ruler-combined', 'طلب صلاحية الموقع', 'معاينة ورفع مساحي وملف نهائي إلكترونياً', null],
            ['citizen-lookup', 'fa-solid fa-helmet-safety', 'الإشراف الهندسي', 'استعلام عن شهادات الإشراف', null],
            ['exam-results.index', 'fa-solid fa-graduation-cap', 'نتائج الامتحانات', 'استعلم عن نتيجتك برقم الجلوس', null],
            ['emergency.create', 'fa-solid fa-truck-medical', 'بلاغ طوارئ', 'أبلغ عن حالة طارئة فوراً', 'danger'],
            ['estidama.index', 'fa-solid fa-seedling', 'برنامج استدامة', 'برامج تدريب وتعلّم معتمدة', null],
            ['market.home', 'fa-solid fa-store', 'متجر ذوي الهمم', 'منتجات أبناء المحافظة', null],
            ['tour-bookings.create', 'fa-solid fa-route', 'حجز رحلة سياحية', 'للشركات والمجموعات', null],
            ['directory.index', 'fa-solid fa-phone-volume', 'دليل الهاتف', 'أرقام الطوارئ والجهات الخدمية', null],
            ['suggestions.create', 'fa-solid fa-lightbulb', 'تقديم مقترح', 'شاركنا أفكارك لتطوير الخدمات', null],
            ['surveys.service.create', 'fa-solid fa-star-half-stroke', 'تقييم الخدمات', 'رأيك يساعدنا على التحسين', null],
        ])->map(fn ($q) => ['url' => $kfsRoute($q[0]), 'icon' => $q[1], 'title' => $q[2], 'desc' => $q[3], 'tone' => $q[4]])
          ->filter(fn ($q) => $q['url'])->values();

        $kfsHasMarket = (bool) $kfsRoute('market.home');
        $kfsPlanUrl = $kfsRoute('investment.plans.index');
    @endphp

    <main class="main-content kfs-home">
        @if (session('success'))
            <script>Swal.fire({ icon:'success', title:'تم بنجاح', text:{{ json_encode(session('success')) }}, confirmButtonText:'حسناً' });</script>
        @endif

        {{-- ======================= 1. HERO ======================= --}}
        <section class="kfs-hs" aria-label="البوابة الرسمية — أبرز الموضوعات">
            @if ($heroSlides->isNotEmpty())
                <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="7000">
                    <div class="carousel-inner">
                        @foreach ($heroSlides as $slide)
                            <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                @if ($slide->media_type == 'video')
                                    <video class="kfs-hs__media" autoplay muted loop playsinline
                                        poster="{{ asset('images/video_placeholder.png') }}" aria-hidden="true">
                                        <source src="{{ Storage::url($slide->media_path) }}" type="video/mp4">
                                    </video>
                                @else
                                    <img src="{{ Storage::url($slide->media_path) }}" class="kfs-hs__media" alt=""
                                        @if (!$loop->first) loading="lazy" @endif>
                                @endif
                                <div class="kfs-hs__shade" aria-hidden="true"></div>
                                <div class="kfs-hs__content">
                                    <div class="container">
                                        <div class="kfs-hs__text kfs-hero-anim">
                                            <span class="kfs-hs__badge">{{ $slide->badge_text ?? ($slide->link_url ? 'رؤية وطنية' : 'محافظة كفر الشيخ') }}</span>
                                            @if ($loop->first)
                                                <h1 class="kfs-hs__title">{{ $slide->title }}</h1>
                                            @else
                                                <h2 class="kfs-hs__title">{{ $slide->title }}</h2>
                                            @endif
                                            <p class="kfs-hs__lead">{{ $slide->description ? \Illuminate\Support\Str::limit($slide->description, 150) : 'نعمل معاً لخدمة المواطن وتحقيق التنمية المستدامة' }}</p>
                                            @if ($slide->link_url)
                                                <div class="kfs-hs__actions">
                                                    <a href="{{ $slide->link_url }}" class="btn-modern-gold">
                                                        <span>{{ $slide->link_text ?? 'استكشف الآن' }}</span>
                                                        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($heroSlides->count() > 1)
                        <div class="kfs-hs__dots">
                            <div class="container">
                                @foreach ($heroSlides as $slide)
                                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="{{ $loop->index }}"
                                        class="{{ $loop->first ? 'active' : '' }}" aria-label="الشريحة {{ $loop->iteration }}"
                                        @if ($loop->first) aria-current="true" @endif></button>
                                @endforeach
                            </div>
                        </div>
                        <div class="kfs-hs__arrows">
                            <div class="container">
                                <button type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" aria-label="الشريحة السابقة"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                                <button type="button" data-bs-target="#heroCarousel" data-bs-slide="next" aria-label="الشريحة التالية"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="carousel-item active d-block">
                    <img src="{{ asset('images/places/i3.png') }}" class="kfs-hs__media" alt="">
                    <div class="kfs-hs__shade" aria-hidden="true"></div>
                    <div class="kfs-hs__content">
                        <div class="container">
                            <div class="kfs-hs__text kfs-hero-anim">
                                <span class="kfs-hs__badge">البوابة الإلكترونية الرسمية</span>
                                <h1 class="kfs-hs__title">محافظة كفر الشيخ</h1>
                                <p class="kfs-hs__lead">خدماتك الحكومية ومعلومات محافظتك في مكان واحد — أرض الماء والزراعة والتنمية.</p>
                                <div class="kfs-hs__actions">
                                    <a href="{{ route('services.index') }}" class="btn-modern-gold"><span>الخدمات الإلكترونية</span><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
                                    <a href="{{ route('about') }}" class="btn-kfs-outline on-dark">اكتشف المحافظة</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        {{-- Quick search overlapping the hero --}}
        <div class="container kfs-quick">
            <form action="{{ route('search') }}" class="kfs-quick__form" role="search">
                <label for="kfsHomeSearch" class="kfs-quick__label"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> ابحث في البوابة</label>
                <input type="search" id="kfsHomeSearch" name="query" placeholder="خدمة، خبر، مشروع، رقم هاتف..." autocomplete="off">
                <button type="submit" class="btn-kfs" aria-label="بحث"><i class="fa-solid fa-magnifying-glass d-md-none" aria-hidden="true"></i><span>بحث</span></button>
            </form>
        </div>

        {{-- ======================= 2. VALUES (secondary hero) ======================= --}}
        @if (!empty($settings['secondary_hero_title']))
            <section class="kfs-sec kfs-sec--tight kfs-sec--flush-b" id="values-section" aria-labelledby="kfsValuesTitle">
                <div class="container">
                    <div class="kfs-values" data-reveal>
                        <div>
                            <h2 class="kfs-values__title" id="kfsValuesTitle">{!! clean($settings['secondary_hero_title'], 'cms') !!}</h2>
                            @if (!empty($settings['secondary_hero_description']))
                                <p class="kfs-values__text">{!! clean($settings['secondary_hero_description'], 'cms') !!}</p>
                            @endif
                            <a href="{{ $settings['secondary_hero_button_link'] ?? '#' }}" class="btn-kfs">
                                <span>{{ $kfsText('secondary_hero_button_text', 'قراءة المزيد') }}</span><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="kfs-values__mark" aria-hidden="true"><img src="{{ $kfsLogoUrl }}" alt="" width="92" height="92" loading="lazy"></div>
                    </div>
                </div>
            </section>
        @endif

        {{-- ======================= 3. EVENTS ======================= --}}
        @if ($events->isNotEmpty())
            <section class="kfs-sec" id="events-section" aria-labelledby="kfsEventsTitle">
                <div class="container">
                    <x-kfs.section-title id="kfsEventsTitle" :subtitle="$kfsText('events_subtitle', 'تابع آخر الفعاليات والقرارات الرسمية.')">
                        {{ $kfsText('events_title', 'أهم الأحداث') }}
                        @if ($events->count() > 1)
                            <x-slot:tools>
                                <button type="button" class="kfs-navbtn kfs-events-prev" aria-label="الحدث السابق"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                                <button type="button" class="kfs-navbtn kfs-events-next" aria-label="الحدث التالي"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                            </x-slot:tools>
                        @endif
                    </x-kfs.section-title>
                    <div class="kfs-slider" data-reveal>
                        <div class="swiper kfs-events-swiper">
                            <div class="swiper-wrapper">
                                @foreach ($events as $event)
                                    <div class="swiper-slide">
                                        <a href="{{ route('posts.show', $event->slug) }}" class="kfs-event">
                                            <span class="kfs-card__media">
                                                <img src="{{ $kfsImg($event->thumbnail) }}" alt="" loading="lazy">
                                                @if ($event->published_at)
                                                    <time class="kfs-date" datetime="{{ $event->published_at->toDateString() }}">
                                                        <b>{{ $event->published_at->format('d') }}</b><span>{{ $event->published_at->translatedFormat('F Y') }}</span>
                                                    </time>
                                                @endif
                                            </span>
                                            <span class="kfs-card__body">
                                                <span><span class="kfs-badge kfs-badge--gold"><i class="fa-regular fa-calendar" aria-hidden="true"></i> فعالية</span></span>
                                                <span class="kfs-card__title">{{ $event->title }}</span>
                                                <span class="kfs-card__more">التفاصيل <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
                                            </span>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- ======================= 4. NEWS (featured + latest) ======================= --}}
        <section class="kfs-sec kfs-sec--alt" aria-labelledby="kfsNewsTitle">
            <div class="container">
                <x-kfs.section-title id="kfsNewsTitle" :subtitle="$kfsText('news_subtitle', 'تحديثات يومية لأهم أخبار المحافظة.')"
                    :link="route('posts.index')" :link-text="$kfsText('news_view_all_link_text', 'عرض كل الأخبار')">
                    {{ $kfsText('news_title', 'آخر الأخبار') }}
                </x-kfs.section-title>

                @if ($newsCategories->isNotEmpty())
                    <nav aria-label="تصنيفات الأخبار">
                        <ul class="kfs-chips">
                            <li><a href="{{ route('posts.index') }}" class="kfs-chip">الكل</a></li>
                            @foreach ($newsCategories as $cat)
                                <li><a href="{{ route('posts.index', ['category' => $cat->slug]) }}" class="kfs-chip">{{ $cat->name }} <span class="kfs-chip__count">{{ $cat->posts_count }}</span></a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                @if ($featuredNews || $latestNews->isNotEmpty())
                    <div class="kfs-news-layout {{ $featuredNews && $latestNews->isNotEmpty() ? '' : 'is-single' }}">
                        @if ($featuredNews)
                            <a href="{{ route('posts.show', $featuredNews->slug) }}" class="kfs-news kfs-news--feature" data-reveal>
                                <span class="kfs-card__media">
                                    <img src="{{ $kfsImg($featuredNews->thumbnail) }}" alt="" loading="lazy">
                                    @if ($featuredNews->published_at)
                                        <time class="kfs-date" datetime="{{ $featuredNews->published_at->toDateString() }}"><b>{{ $featuredNews->published_at->format('d') }}</b><span>{{ $featuredNews->published_at->translatedFormat('F Y') }}</span></time>
                                    @endif
                                </span>
                                <span class="kfs-card__body">
                                    <span><span class="kfs-badge">{{ $featuredNews->category->name ?? 'أخبار' }}</span></span>
                                    <span class="kfs-card__title">{{ $featuredNews->title }}</span>
                                    <span class="kfs-card__text">{{ $kfsPlain($featuredNews->content, 220) }}</span>
                                    <span class="kfs-card__more">اقرأ الخبر <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
                                </span>
                            </a>
                        @endif
                        @if ($latestNews->isNotEmpty())
                            <div class="kfs-news-layout__side" data-stagger>
                                @foreach ($latestNews as $news)
                                    <a href="{{ route('posts.show', $news->slug) }}" class="kfs-news {{ $featuredNews ? 'kfs-news--row' : '' }}">
                                        <span class="kfs-card__media"><img src="{{ $kfsImg($news->thumbnail) }}" alt="" loading="lazy"></span>
                                        <span class="kfs-card__body">
                                            <span class="kfs-card__meta">
                                                <span class="kfs-badge">{{ $news->category->name ?? 'أخبار' }}</span>
                                                @if ($news->published_at)
                                                    <time datetime="{{ $news->published_at->toDateString() }}"><i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $news->published_at->translatedFormat('d F Y') }}</time>
                                                @endif
                                            </span>
                                            <span class="kfs-card__title">{{ $news->title }}</span>
                                            <span class="kfs-card__text">{{ $kfsPlain($news->content, 120) }}</span>
                                            <span class="kfs-card__more">اقرأ المزيد <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <x-kfs.empty-state icon="docs" title="لا توجد أخبار منشورة بعد" compact
                        text="ستظهر هنا آخر أخبار المحافظة وقراراتها فور نشرها.">
                        <a href="{{ route('posts.index') }}" class="btn-kfs-outline">أرشيف الأخبار</a>
                    </x-kfs.empty-state>
                @endif
            </div>
        </section>

        {{-- ======================= 5. SERVICES ======================= --}}
        <section class="kfs-sec" id="services-section" aria-labelledby="kfsSvcTitle">
            <div class="container">
                <x-kfs.section-title id="kfsSvcTitle" :subtitle="$kfsText('services_subtitle', 'أنجز معاملاتك الحكومية من أي مكان — اختر الخدمة وابدأ مباشرة.')"
                    :link="route('services.index')" :link-text="$kfsText('services_view_all_link_text', 'عرض جميع الخدمات')">
                    {{ $kfsText('services_title', 'خدمات المحافظة') }}
                </x-kfs.section-title>

                @if ($services->isNotEmpty())
                    <div class="kfs-grid kfs-grid--3 mb-4" data-stagger>
                        @foreach ($services as $service)
                            <a href="{{ $service->link ? $service->link : route('services.show', $service) }}"
                                @if ($service->link) target="_blank" rel="noopener" @endif
                                class="kfs-svc kfs-svc--feature">
                                <span class="kfs-svc__icon" aria-hidden="true"><i class="{{ $service->icon ?: 'fa-solid fa-file-lines' }}"></i></span>
                                <span class="kfs-svc__body">
                                    <span class="kfs-svc__title">
                                        @if ($service->title_line_1 || $service->title_line_2)
                                            {{ trim(strip_tags($service->title_line_1 . ' ' . $service->title_line_2)) }}
                                        @else
                                            {{ strip_tags($service->title) }}
                                        @endif
                                    </span>
                                    @if ($service->description)
                                        <span class="kfs-svc__desc">{{ $kfsPlain($service->description, 110) }}</span>
                                    @endif
                                    <span class="kfs-card__more">ابدأ الخدمة <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($kfsQuick->isNotEmpty())
                    <div class="kfs-grid kfs-grid--4" data-stagger>
                        @foreach ($kfsQuick as $q)
                            <a href="{{ $q['url'] }}" class="kfs-svc {{ $q['tone'] ? 'kfs-svc--' . $q['tone'] : '' }}">
                                <span class="kfs-svc__icon" aria-hidden="true"><i class="{{ $q['icon'] }}"></i></span>
                                <span class="kfs-svc__body">
                                    <span class="kfs-svc__title">{{ $q['title'] }}</span>
                                    <span class="kfs-svc__desc">{{ $q['desc'] }}</span>
                                </span>
                                <span class="kfs-card__more">ابدأ الخدمة <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- ======================= 6. TOURISM (bento showcase) ======================= --}}
        @if ($landmarks->isNotEmpty())
            @php
                $kfsTourFeature = $landmarks->first();
                $kfsTourRest = $landmarks->slice(1, 4);
                $kfsTourMore = max(0, $landmarks->count() - 5);
            @endphp
            <section class="kfs-sec kfs-tourx" aria-labelledby="kfsTourTitle">
                <div class="kfs-tourx__pattern" aria-hidden="true"></div>
                <div class="container position-relative">
                    <x-kfs.section-title id="kfsTourTitle" dark
                        eyebrow="سياحة وتراث"
                        :subtitle="$kfsText('tourism_subtitle', 'اكتشف أبرز المواقع التاريخية والطبيعية التي تميز محافظة كفر الشيخ.')"
                        :link="route('landmarks.index')" :link-text="$kfsText('tourism_view_all_link_text', 'استكشف كل المعالم')">
                        {{ $kfsText('tourism_title', 'أهم المعالم السياحية في المحافظة') }}
                    </x-kfs.section-title>

                    <div class="kfs-tourx__grid" data-stagger>
                        {{-- Featured landmark --}}
                        <article class="kfs-tourx__card kfs-tourx__card--feature" data-reveal>
                            <img src="{{ $kfsImg($kfsTourFeature->thumbnail) }}" alt="" loading="lazy">
                            <div class="kfs-tourx__body">
                                @if ($kfsTourFeature->address)
                                    <span class="kfs-tourx__loc"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ \Illuminate\Support\Str::limit($kfsTourFeature->address, 60) }}</span>
                                @endif
                                <h3 class="kfs-tourx__title">
                                    <a href="{{ route('landmarks.show', $kfsTourFeature) }}" class="kfs-tourx__link">{{ $kfsTourFeature->name }}</a>
                                </h3>
                                @if ($kfsPlain($kfsTourFeature->details, 170))
                                    <p class="kfs-tourx__excerpt">{{ $kfsPlain($kfsTourFeature->details, 170) }}</p>
                                @endif
                                <div class="kfs-tourx__actions">
                                    <span class="kfs-tourx__cta">اكتشف المكان <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
                                    @if ($kfsTourFeature->latitude && $kfsTourFeature->longitude)
                                        <a class="kfs-tourx__map" href="https://www.google.com/maps?q={{ $kfsTourFeature->latitude }},{{ $kfsTourFeature->longitude }}" target="_blank" rel="noopener">
                                            <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> على الخريطة
                                            <span class="visually-hidden">(يفتح في نافذة جديدة)</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </article>

                        @foreach ($kfsTourRest as $landmark)
                            <article class="kfs-tourx__card" data-reveal>
                                <img src="{{ $kfsImg($landmark->thumbnail) }}" alt="" loading="lazy">
                                <div class="kfs-tourx__body">
                                    @if ($landmark->address)
                                        <span class="kfs-tourx__loc"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ \Illuminate\Support\Str::before($landmark->address, ' - ') }}</span>
                                    @endif
                                    <h3 class="kfs-tourx__title">
                                        <a href="{{ route('landmarks.show', $landmark) }}" class="kfs-tourx__link">{{ $landmark->name }}</a>
                                    </h3>
                                </div>
                                <span class="kfs-tourx__arrow" aria-hidden="true"><i class="fa-solid fa-arrow-left"></i></span>
                            </article>
                        @endforeach
                    </div>

                    <div class="kfs-tourx__foot" data-reveal>
                        <p class="kfs-tourx__count">
                            <strong>{{ $landmarks->count() }}</strong>
                            <span>{{ $landmarks->count() > 10 ? 'معلمًا' : 'معالم' }} سياحية وتاريخية في انتظارك{{ $kfsTourMore ? ' — وغيرها '.$kfsTourMore.' في دليل المعالم' : '' }}</span>
                        </p>
                        <div class="kfs-tourx__btns">
                            <a href="{{ route('landmarks.index') }}" class="kfs-tourx__btn kfs-tourx__btn--gold">
                                <i class="fa-solid fa-compass" aria-hidden="true"></i> دليل المعالم
                            </a>
                            @if (Route::has('tour-bookings.create'))
                                <a href="{{ route('tour-bookings.create') }}" class="kfs-tourx__btn">
                                    <i class="fa-solid fa-route" aria-hidden="true"></i> احجز رحلة
                                </a>
                            @elseif (Route::has('tour-bookings.index'))
                                <a href="{{ route('tour-bookings.index') }}" class="kfs-tourx__btn">
                                    <i class="fa-solid fa-route" aria-hidden="true"></i> احجز رحلة
                                </a>
                            @endif
                        </div>
                    </div>

                    @if (!empty($settings['tourism_tagline_image']))
                        <div class="text-center mt-4" data-reveal>
                            <img src="{{ Storage::url($settings['tourism_tagline_image']) }}" alt="كل مكان ليه حكاية مختلفة" class="img-fluid kfs-tourx__tagline" loading="lazy" onerror="this.parentNode.remove()" />
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- ======================= 7. INVESTMENT ======================= --}}
        @if ($investments->isNotEmpty())
            <section class="kfs-sec" aria-labelledby="kfsInvTitle">
                <div class="container">
                    <x-kfs.section-title id="kfsInvTitle" :subtitle="$kfsText('investment_subtitle', 'اكتشف أبرز الفرص الاستثمارية.')"
                        :link="route('investments.index')" :link-text="$kfsText('investment_view_all_link_text', 'عرض الفرص الاستثمارية')">
                        {{ $kfsText('investment_title', 'الاستثمار في المحافظة') }}
                    </x-kfs.section-title>
                    <div class="kfs-grid kfs-grid--2" data-stagger>
                        @foreach ($investments as $investment)
                            <a href="{{ route('investments.show', $investment) }}" class="kfs-inv">
                                <span class="kfs-card__media"><img src="{{ $kfsImg($investment->thumbnail) }}" alt="" loading="lazy"></span>
                                <span class="kfs-card__body">
                                    <span><span class="kfs-badge kfs-badge--gold"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> فرصة استثمارية</span></span>
                                    <span class="kfs-card__title">{{ $investment->title }}</span>
                                    @if ($investment->description)
                                        <span class="kfs-card__text">{{ $kfsPlain($investment->description, 120) }}</span>
                                    @endif
                                    <span class="kfs-card__more">تفاصيل الفرصة <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- ======================= 8. PROJECTS (national & Hayah Karima) ======================= --}}
        @if ($nationalProjects->isNotEmpty())
            <section class="kfs-sec kfs-sec--alt" aria-labelledby="kfsProjTitle">
                <div class="container">
                    <x-kfs.section-title id="kfsProjTitle" title="المشروعات القومية"
                        subtitle="مشروعات التنمية ومبادرة حياة كريمة في مراكز المحافظة."
                        :link="route('projects.index')" link-text="عرض كل المشروعات" />
                    <div class="kfs-grid kfs-grid--3" data-stagger>
                        @foreach ($nationalProjects as $project)
                            <a href="{{ route('projects.show', $project) }}" class="kfs-media">
                                <span class="kfs-card__media"><img src="{{ $kfsImg($project->thumbnail) }}" alt="" loading="lazy"></span>
                                <span class="kfs-card__body">
                                    @if ($project->category)
                                        <span><span class="kfs-badge {{ $project->category === 'حياة كريمة' ? 'kfs-badge--olive' : '' }}">{{ $project->category }}</span></span>
                                    @endif
                                    <span class="kfs-card__title">{{ $project->name }}</span>
                                    <span class="kfs-card__more">تفاصيل المشروع <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- ======================= 9. STRATEGIC INVESTMENT PLAN CTA ======================= --}}
        @if ($kfsPlanUrl)
            <section class="kfs-sec" aria-labelledby="kfsPlanTitle">
                <div class="container">
                    <div class="kfs-plan" data-reveal>
                        <div class="kfs-plan__body">
                            <span class="kfs-badge kfs-badge--gold">خارطة التنمية</span>
                            <h2 class="kfs-plan__title" id="kfsPlanTitle">مشروعات الخطة الاستثمارية لمحافظة كفر الشيخ</h2>
                            <p class="kfs-plan__text">اطّلع على الخطة الاستثمارية السنوية للمحافظة والتقارير الموثقة للعام الحالي والأعوام السابقة — رؤية وطنية لتحسين البنية التحتية والخدمات وتوطين التنمية المستدامة في كافة المراكز.</p>
                            <a href="{{ $kfsPlanUrl }}" class="btn-kfs-gold"><span>استكشاف الخطة الكاملة</span><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                        </div>
                        <div class="kfs-plan__media" style="background-image: url('{{ asset('images/bg/investment-plan.jpg') }}');" aria-hidden="true">
                            <span class="kfs-plan__logo"><img src="{{ asset('images/egypt-vision.png') }}" alt="" loading="lazy"></span>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- ======================= 10. ACHIEVEMENTS ======================= --}}
        @if ($achievements->isNotEmpty())
            <section class="kfs-sec kfs-sec--alt" aria-labelledby="kfsAchTitle">
                <div class="container">
                    <div class="row g-4 g-lg-5 align-items-center">
                        <div class="col-lg-6">
                            <x-kfs.section-title id="kfsAchTitle" class="mb-2">{{ $kfsText('achievements_title', 'إنجازات الدولة') }}</x-kfs.section-title>
                            @if (!empty($settings['achievements_description']))
                                <p class="text-muted mb-0">{!! clean($settings['achievements_description'], 'cms') !!}</p>
                            @endif
                            <ul class="kfs-checklist" data-stagger>
                                @foreach ($achievements as $achievement)
                                    <li><i class="fa-solid fa-check" aria-hidden="true"></i><span>{{ $achievement->title }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-lg-6">
                            <div class="kfs-ach__media" data-reveal>
                                <img src="{{ $featuredNews && $featuredNews->thumbnail ? Storage::url($featuredNews->thumbnail) : asset('images/image1.png') }}" alt="صورة من إنجازات المحافظة" loading="lazy">
                                @if ($mainAchievementVideo && $mainAchievementVideo->value)
                                    <a href="{{ $mainAchievementVideo->value }}" target="_blank" rel="noopener" class="kfs-ach__play"
                                        aria-label="تشغيل فيديو الإنجازات (يفتح في نافذة جديدة)"><i class="fa-solid fa-play" aria-hidden="true"></i></a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- ======================= 11. PARTNER APPS (tabs) ======================= --}}
        @if ($partners->isNotEmpty())
            <section class="kfs-sec" aria-labelledby="kfsAppsTitle">
                <div class="container">
                    <x-kfs.section-title id="kfsAppsTitle" :subtitle="$kfsText('apps_subtitle', '') ?: null">{{ $kfsText('apps_title', 'مواقع تهمك') }}</x-kfs.section-title>
                    <div class="kfs-apps" data-reveal>
                        <div class="kfs-apps__list" role="tablist" aria-label="المواقع والتطبيقات">
                            @foreach ($partners as $partner)
                                <button type="button" class="kfs-apps__tab" role="tab" id="kfsAppTab{{ $loop->index }}"
                                    aria-controls="kfsAppPanel{{ $loop->index }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                    tabindex="{{ $loop->first ? '0' : '-1' }}">
                                    <span>{{ $partner->name }}</span><i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                                </button>
                            @endforeach
                        </div>
                        <div>
                            @foreach ($partners as $partner)
                                <div class="kfs-apps__panel" role="tabpanel" id="kfsAppPanel{{ $loop->index }}" aria-labelledby="kfsAppTab{{ $loop->index }}" @unless ($loop->first) hidden @endunless>
                                    <div class="kfs-apps__img"><img src="{{ $kfsImg($partner->image) }}" alt="" loading="lazy"></div>
                                    <div class="kfs-apps__text">
                                        <h3>{{ $partner->name }}</h3>
                                        @if ($partner->description)
                                            <p>{{ $partner->description }}</p>
                                        @endif
                                        @if ($partner->link)
                                            <a href="{{ $partner->link }}" target="_blank" rel="noopener" class="btn-kfs"><span>زيارة الموقع الإلكتروني</span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- ======================= 12. STATISTICS («المحافظة في أرقام») ======================= --}}
        @if ($statistics->isNotEmpty())
            @php
                $kfsStatLead = $statistics->first();
                $kfsStatRest = $statistics->slice(1);
            @endphp
            <section class="kfs-sec kfs-numx" aria-labelledby="kfsStatsTitle">
                <div class="kfs-numx__bg" aria-hidden="true">
                    <svg class="kfs-numx__waves" viewBox="0 0 1440 320" preserveAspectRatio="none" focusable="false">
                        <path d="M0 210 C240 170 480 250 720 210 S1200 170 1440 210" />
                        <path d="M0 245 C240 205 480 285 720 245 S1200 205 1440 245" />
                        <path d="M0 280 C240 240 480 320 720 280 S1200 240 1440 280" />
                    </svg>
                </div>
                <div class="container position-relative">
                    <div class="kfs-numx__layout">
                        <div class="kfs-numx__intro" data-reveal>
                            <span class="kfs-numx__eyebrow"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i> بيانات ومؤشرات رسمية</span>
                            <h2 class="kfs-numx__title" id="kfsStatsTitle">{{ $kfsText('stats_title', 'المحافظة في أرقام') }}</h2>
                            <p class="kfs-numx__sub">{{ $kfsText('stats_subtitle', 'نظرة سريعة على أبرز مؤشرات محافظة كفر الشيخ: السكان والمساحة والخدمات والتنمية.') }}</p>

                            {{-- Lead figure --}}
                            <div class="kfs-numx__lead">
                                <span class="kfs-numx__lead-ico" aria-hidden="true"><i class="{{ $kfsStatLead->icon_class }}"></i></span>
                                <div class="kfs-numx__lead-num">
                                    <span class="kfs-count" data-count="{{ $kfsStatLead->number }}" data-decimals="0">{{ number_format($kfsStatLead->number) }}</span>
                                </div>
                                <p class="kfs-numx__lead-label">{{ $kfsStatLead->title }}</p>
                                <svg class="kfs-numx__spark" viewBox="0 0 220 60" aria-hidden="true" focusable="false">
                                    <path class="kfs-numx__spark-area" d="M0 52 L30 46 L60 48 L90 36 L120 38 L150 24 L180 26 L220 8 L220 60 L0 60Z" />
                                    <path class="kfs-numx__spark-line" d="M0 52 L30 46 L60 48 L90 36 L120 38 L150 24 L180 26 L220 8" />
                                </svg>
                            </div>

                            @if ($statistics->max('updated_at'))
                                <p class="kfs-numx__note">
                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    آخر تحديث للبيانات: {{ \Illuminate\Support\Carbon::parse($statistics->max('updated_at'))->locale('ar')->translatedFormat('j F Y') }}
                                </p>
                            @endif
                        </div>

                        @if ($kfsStatRest->isNotEmpty())
                            <ul class="kfs-numx__grid" role="list" data-stagger>
                                @foreach ($kfsStatRest as $stat)
                                    <li class="kfs-numx__tile" data-reveal>
                                        <span class="kfs-numx__ico" aria-hidden="true"><i class="{{ $stat->icon_class }}"></i></span>
                                        <span class="kfs-numx__num"><span class="kfs-count" data-count="{{ $stat->number }}" data-decimals="0">{{ number_format($stat->number) }}</span></span>
                                        <span class="kfs-numx__label">{{ $stat->title }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- ======================= 13. CITY GUIDE (map) ======================= --}}
        @if ($guideCategories->isNotEmpty())
            <section class="kfs-sec" aria-labelledby="kfsGuideTitle">
                <div class="container">
                    <x-kfs.section-title id="kfsGuideTitle" subtitle="أماكن الخدمات والمرافق على الخريطة — اختر تصنيفاً لعرضه."
                        :link="$kfsRoute('governorate.map')" link-text="الخريطة التفاعلية الكاملة">
                        {{ $kfsText('city_guide_title', 'دليل العاصمة') }}
                    </x-kfs.section-title>
                    <div class="kfs-guide" data-reveal>
                        <div class="kfs-guide__side">
                            <ul class="kfs-guide__cats" aria-label="تصنيفات دليل المدينة">
                                <li><button type="button" class="kfs-guide__cat" data-category-id="all" aria-pressed="true">
                                    <span class="kfs-guide__ico"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i></span><span>عرض الكل</span>
                                    <span class="kfs-guide__count">{{ $allLocations->count() }}</span></button></li>
                                @foreach ($guideCategories as $category)
                                    <li><button type="button" class="kfs-guide__cat" data-category-id="{{ $category->id }}" aria-pressed="false">
                                        <span class="kfs-guide__ico"><i class="{{ $category->icon_class }}" aria-hidden="true"></i></span><span>{{ $category->name }}</span>
                                        <span class="kfs-guide__count">{{ $category->locations_count ?? $category->locations->count() }}</span></button></li>
                                @endforeach
                            </ul>
                        </div>
                        <div id="interactiveMap" class="kfs-guide__map" role="region" aria-label="خريطة دليل المدينة"></div>
                    </div>
                </div>
            </section>
        @endif

        {{-- ======================= Additions after the original order ======================= --}}
        @if ($kfsHasMarket)
            <section class="kfs-sec kfs-sec--tight" aria-labelledby="kfsMktTitle">
                <div class="container">
                    <div class="kfs-promo" data-reveal>
                        <span class="kfs-promo__icon" aria-hidden="true"><i class="fa-solid fa-hand-holding-heart"></i></span>
                        <div>
                            <h2 id="kfsMktTitle">متجر ذوي الهمم</h2>
                            <p>منتجات يدوية وإبداعات أصيلة من أبناء المحافظة من ذوي الهمم — كل طلب منك دعمٌ مباشر لهم.</p>
                        </div>
                        <div class="kfs-promo__actions">
                            <a href="{{ route('market.home') }}" class="btn-kfs"><span>تسوّق الآن</span><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                            @if ($kfsRoute('market.sell'))
                                <a href="{{ $kfsRoute('market.sell') }}" class="btn-kfs-outline">انضم كبائع</a>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section class="kfs-sec kfs-sec--tight pt-0" aria-labelledby="kfsLinksTitle">
            <div class="container">
                <h2 class="h5 mb-3" id="kfsLinksTitle">بوابات حكومية مهمة</h2>
                <ul class="kfs-links">
                    <li><a href="https://www.egypt.gov.eg" target="_blank" rel="noopener"><i class="fa-solid fa-desktop" aria-hidden="true"></i>بوابة مصر الرقمية</a></li>
                    <li><a href="https://jobs.egypt.gov.eg" target="_blank" rel="noopener"><i class="fa-solid fa-briefcase" aria-hidden="true"></i>بوابة الوظائف الحكومية</a></li>
                    <li><a href="https://etenders.gov.eg" target="_blank" rel="noopener"><i class="fa-solid fa-file-signature" aria-hidden="true"></i>بوابة التعاقدات العامة</a></li>
                    <li><a href="https://www.takaful.gov.eg" target="_blank" rel="noopener"><i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i>التضامن الاجتماعي</a></li>
                    <li><a href="https://www.sis.gov.eg" target="_blank" rel="noopener"><i class="fa-solid fa-circle-info" aria-hidden="true"></i>الهيئة العامة للاستعلامات</a></li>
                </ul>
            </div>
        </section>
    </main>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var rtl = (document.documentElement.getAttribute('dir') || 'rtl') === 'rtl';
            var calm = function () { return !(window.KFSMotion && window.KFSMotion.ok && window.KFSMotion.ok()); };

            // Events + tourism sliders (no autoplay — calm by default).
            if (window.Swiper) {
                if (document.querySelector('.kfs-events-swiper')) {
                    new Swiper('.kfs-events-swiper', {
                        slidesPerView: 1.12, spaceBetween: 16, speed: calm() ? 0 : 450, watchOverflow: true,
                        navigation: { nextEl: '.kfs-events-next', prevEl: '.kfs-events-prev' },
                        breakpoints: { 576: { slidesPerView: 2, spaceBetween: 20 }, 992: { slidesPerView: 3, spaceBetween: 24 } },
                        a11y: { prevSlideMessage: 'السابق', nextSlideMessage: 'التالي' }
                    });
                }
            }

            // Partner apps: accessible tabs (click + arrow keys).
            var tabs = Array.prototype.slice.call(document.querySelectorAll('.kfs-apps__tab'));
            function selectTab(t, focus) {
                tabs.forEach(function (x) {
                    var on = x === t;
                    x.setAttribute('aria-selected', on ? 'true' : 'false');
                    x.tabIndex = on ? 0 : -1;
                    var p = document.getElementById(x.getAttribute('aria-controls'));
                    if (p) p.hidden = !on;
                });
                if (focus) t.focus();
            }
            tabs.forEach(function (t, i) {
                t.addEventListener('click', function () { selectTab(t); });
                t.addEventListener('keydown', function (e) {
                    var next = { ArrowDown: 1, ArrowUp: -1, ArrowLeft: rtl ? 1 : -1, ArrowRight: rtl ? -1 : 1 }[e.key];
                    if (next) { e.preventDefault(); selectTab(tabs[(i + next + tabs.length) % tabs.length], true); }
                });
            });
        });
    </script>
    @if ($guideCategories->isNotEmpty())
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (!document.getElementById('interactiveMap') || typeof L === 'undefined') return;
                const locations = @json($allLocations);
                const map = L.map('interactiveMap', { scrollWheelZoom: false }).setView([31.1143, 30.9416], 13);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19
                }).addTo(map);

                const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                const markers = {};
                const all = [];
                locations.forEach(location => {
                    if (!location.latitude || !location.longitude) return;
                    const icon = L.divIcon({
                        className: 'custom-leaflet-icon',
                        html: `<i class="${esc(location.city_guide_category ? location.city_guide_category.icon_class : 'fa-solid fa-location-dot')}"></i>`,
                        iconSize: [30, 30],
                    });
                    const marker = L.marker([location.latitude, location.longitude], { icon: icon })
                        .addTo(map)
                        .bindPopup(`<div class="popup-title">${esc(location.name)}</div>
                            <a class="popup-link" href="https://maps.google.com/?q=${encodeURIComponent(location.latitude + ',' + location.longitude)}" target="_blank" rel="noopener">عرض على خرائط جوجل</a>`);
                    (markers[location.city_guide_category_id] = markers[location.city_guide_category_id] || []).push(marker);
                    all.push(marker);
                });
                if (all.length) { map.fitBounds(L.featureGroup(all).getBounds().pad(0.15)); }

                document.querySelectorAll('.kfs-guide__cat').forEach(button => {
                    button.addEventListener('click', function () {
                        document.querySelectorAll('.kfs-guide__cat').forEach(btn => btn.setAttribute('aria-pressed', 'false'));
                        this.setAttribute('aria-pressed', 'true');
                        const id = this.dataset.categoryId;
                        all.forEach(m => map.removeLayer(m));
                        (id === 'all' ? all : (markers[id] || [])).forEach(m => m.addTo(map));
                    });
                });
            });
        </script>
    @endif
@endpush
