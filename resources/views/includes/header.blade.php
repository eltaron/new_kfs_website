@php
    $kfsLogo = !empty($settings['site_logo_header']) ? Storage::url($settings['site_logo_header']) : asset('logo.png');
    $kfsHas = fn (string $name) => \Illuminate\Support\Facades\Route::has($name);
    $kfsActive = fn (...$patterns) => request()->routeIs(...$patterns);
@endphp
<!-- ======================= Header Start ======================= -->
<header class="header-main kfs-header" role="banner">
    {{-- ===== Government top bar ===== --}}
    <div class="kfs-govbar" id="top-bar">
        <div class="container kfs-govbar__inner">
            <p class="kfs-govbar__title">
                <strong>جمهورية مصر العربية</strong>
                <span class="sep" aria-hidden="true">|</span>
                <span>محافظة كفر الشيخ<span class="kfs-govbar__long"> — البوابة الإلكترونية الرسمية</span></span>
            </p>
            <div class="kfs-govbar__date d-none d-xxl-block" id="date-time-container"></div>
            <ul class="kfs-govbar__tools">
                <li class="d-none d-lg-block">
                    <span class="kfs-trial-pill"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> نسخة تجريبية</span>
                </li>
                <li>
                    <button type="button" class="kfs-govbar__btn" data-a11y-open aria-label="أدوات الإتاحة">
                        <i class="fa-solid fa-universal-access" aria-hidden="true"></i><span class="lbl">الإتاحة</span>
                    </button>
                </li>
                <li>
                    <button type="button" class="kfs-govbar__btn" data-voice-help aria-label="قائمة الأوامر الصوتية">
                        <i class="fa-solid fa-microphone-lines" aria-hidden="true"></i><span class="lbl">الأوامر الصوتية</span>
                    </button>
                </li>
                <li class="d-none d-md-block">
                    <button type="button" class="kfs-govbar__btn" data-motion-toggle aria-pressed="false" title="إيقاف/تشغيل الحركة والمؤثرات">
                        <i class="fa-solid fa-circle-pause" aria-hidden="true"></i><span class="lbl">إيقاف الحركة</span>
                    </button>
                </li>
                <li class="language-switcher dropdown">
                    <button type="button" class="kfs-govbar__btn dropdown-toggle" data-bs-toggle="dropdown"
                        aria-expanded="false" aria-label="اختيار اللغة">
                        <i class="fa-solid fa-globe" aria-hidden="true"></i><span class="lbl">اللغة</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end text-center">
                        <li><button type="button" class="dropdown-item" onclick="setLanguage('ar')" lang="ar">العربية</button></li>
                        <li><button type="button" class="dropdown-item" onclick="setLanguage('en')" lang="en">English</button></li>
                        <li><button type="button" class="dropdown-item" onclick="setLanguage('fr')" lang="fr">Français</button></li>
                        <li><button type="button" class="dropdown-item" onclick="setLanguage('de')" lang="de">Deutsch</button></li>
                        <li><button type="button" class="dropdown-item" onclick="setLanguage('es')" lang="es">Español</button></li>
                        <li><button type="button" class="dropdown-item" onclick="setLanguage('zh-CN')" lang="zh">中文</button></li>
                        <li><button type="button" class="dropdown-item" onclick="setLanguage('ja')" lang="ja">日本語</button></li>
                        <li><button type="button" class="dropdown-item" onclick="setLanguage('ko')" lang="ko">한국어</button></li>
                    </ul>
                    <div id="google_translate_element"></div>
                </li>
                <li class="d-none d-sm-block">
                    <button type="button" class="kfs-govbar__btn" data-search-open aria-label="البحث في البوابة">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span class="lbl">بحث</span>
                    </button>
                </li>
                <li class="d-none d-md-block">
                    <a class="kfs-govbar__btn" href="{{ route('contact.index') }}">
                        <i class="fa-solid fa-headset" aria-hidden="true"></i><span class="lbl">تواصل</span>
                    </a>
                </li>
                <li>
                    <button type="button" class="kfs-govbar__btn" data-pwa-install hidden aria-label="تثبيت تطبيق البوابة">
                        <i class="fa-solid fa-download" aria-hidden="true"></i><span class="lbl">تثبيت التطبيق</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    {{-- ===== Main navigation ===== --}}
    <nav class="navbar navbar-expand-xl kfs-nav main-nav" aria-label="القائمة الرئيسية">
        <div class="container">
            <a class="navbar-brand kfs-brand" href="{{ route('home') }}" aria-label="الصفحة الرئيسية — محافظة كفر الشيخ">
                <img src="{{ $kfsLogo }}" alt="شعار محافظة كفر الشيخ" class="logo-img" width="70" height="54" />
                <span class="kfs-brand__text">
                    <span class="kfs-brand__name">محافظة كفر الشيخ</span>
                    <span class="kfs-brand__sub">البوابة الإلكترونية الرسمية</span>
                </span>
            </a>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ $kfsActive('home') ? 'active' : '' }}" href="{{ route('home') }}"
                            @if ($kfsActive('home')) aria-current="page" @endif>الرئيسية<span class="kfs-active-dot" aria-hidden="true"></span></a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ $kfsActive('about', 'about.*', 'officials.*', 'governorate.*', 'landmarks.*', 'investments.*', 'investment.*') ? 'active' : '' }}"
                            href="#" id="aboutDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            المحافظة<span class="kfs-active-dot" aria-hidden="true"></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="aboutDropdown">
                            <li><a class="dropdown-item" href="{{ route('about') }}"><i class="fa-solid fa-landmark-flag" aria-hidden="true"></i>عن المحافظة</a></li>
                            <li><a class="dropdown-item" href="{{ route('about.governor') }}"><i class="fa-solid fa-feather-pointed" aria-hidden="true"></i>كلمة المحافظ</a></li>
                            <li class="dropdown-submenu">
                                <a class="dropdown-item" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa-solid fa-user-tie" aria-hidden="true"></i>قيادات المحافظة
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('officials.show', ['role' => 'governor']) }}">المحافظ</a></li>
                                    <li><a class="dropdown-item" href="{{ route('officials.show', ['role' => 'deputy-governor']) }}">نائب المحافظ</a></li>
                                    <li><a class="dropdown-item" href="{{ route('officials.show', ['role' => 'secretary-general']) }}">السكرتير العام</a></li>
                                    <li><a class="dropdown-item" href="{{ route('officials.show', ['role' => 'assistant-secretary-general']) }}">السكرتير العام المساعد</a></li>
                                </ul>
                            </li>
                            <li><a class="dropdown-item" href="{{ route('landmarks.index') }}"><i class="fa-solid fa-monument" aria-hidden="true"></i>السياحة والمعالم</a></li>
                            <li><a class="dropdown-item" href="{{ route('investments.index') }}"><i class="fa-solid fa-chart-line" aria-hidden="true"></i>الاستثمار</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ $kfsActive('services.*', 'gis.*', 'site-validity.*', 'estidama.*', 'market.*', 'citizen-lookup*', 'exam-results.*', 'emergency.*') ? 'active' : '' }}"
                            href="#" id="servicesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            الخدمات الإلكترونية<span class="kfs-active-dot" aria-hidden="true"></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="servicesDropdown">
                            <li><a class="dropdown-item" href="{{ route('services.index') }}"><i class="fa-solid fa-hand-holding-hand" aria-hidden="true"></i>دليل الخدمات</a></li>
                            @if ($kfsHas('gis.index'))
                                <li><a class="dropdown-item" href="{{ route('gis.index') }}"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>بوابة الخدمات الجغرافية</a></li>
                            @endif
                            @if ($kfsHas('site-validity.landing'))
                                <li><a class="dropdown-item" href="{{ route('site-validity.landing') }}"><i class="fa-solid fa-ruler-combined" aria-hidden="true"></i>طلب صلاحية الموقع</a></li>
                            @endif
                            @if ($kfsHas('estidama.index'))
                                <li><a class="dropdown-item" href="{{ route('estidama.index') }}"><i class="fa-solid fa-seedling" aria-hidden="true"></i>برنامج استدامة</a></li>
                            @endif
                            @if ($kfsHas('market.home'))
                                <li><a class="dropdown-item" href="{{ route('market.home') }}"><i class="fa-solid fa-store" aria-hidden="true"></i>المتجر الإلكتروني</a></li>
                            @endif
                            @if ($kfsHas('syndicate.verify.form'))
                                <li><a class="dropdown-item" href="{{ route('syndicate.verify.form') }}"><i class="fa-solid fa-helmet-safety" aria-hidden="true"></i>التحقق من شهادة الإشراف الهندسي</a></li>
                            @endif
                            @if ($kfsHas('lms.certificates.verify'))
                                <li><a class="dropdown-item" href="{{ route('lms.certificates.verify') }}"><i class="fa-solid fa-certificate" aria-hidden="true"></i>التحقق من شهادة تدريب</a></li>
                            @endif
                            @if ($kfsHas('exam-results.index'))
                                <li><a class="dropdown-item" href="{{ route('exam-results.index') }}"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>نتائج الامتحانات</a></li>
                            @endif
                            @if ($kfsHas('emergency.create'))
                                <li><a class="dropdown-item" href="{{ route('emergency.create') }}"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>بلاغ طوارئ</a></li>
                            @endif
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $kfsActive('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}"
                            @if ($kfsActive('projects.*')) aria-current="page" @endif>المشروعات<span class="kfs-active-dot" aria-hidden="true"></span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $kfsActive('posts.*') ? 'active' : '' }}" href="{{ route('posts.index') }}"
                            @if ($kfsActive('posts.*')) aria-current="page" @endif>الأخبار<span class="kfs-active-dot" aria-hidden="true"></span></a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ $kfsActive('governorate.map', 'investment.plans.*', 'projects.hay-karima', 'directory.*', 'advertising.*') ? 'active' : '' }}"
                            href="#" id="dataDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            البيانات والمعلومات<span class="kfs-active-dot" aria-hidden="true"></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="dataDropdown">
                            <li><a class="dropdown-item" href="{{ route('governorate.map') }}"><i class="fa-solid fa-map" aria-hidden="true"></i>الخريطة التفاعلية</a></li>
                            @if ($kfsHas('investment.plans.index'))
                                <li><a class="dropdown-item" href="{{ route('investment.plans.index') }}"><i class="fa-solid fa-file-invoice" aria-hidden="true"></i>الخطة الاستثمارية</a></li>
                            @endif
                            @if ($kfsHas('projects.hay-karima'))
                                <li><a class="dropdown-item" href="{{ route('projects.hay-karima') }}"><i class="fa-solid fa-house-flag" aria-hidden="true"></i>مشروعات حياة كريمة</a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('directory.index') }}"><i class="fa-solid fa-address-book" aria-hidden="true"></i>دليل الهاتف</a></li>
                            @if ($kfsHas('advertising.guide'))
                                <li><a class="dropdown-item" href="{{ route('advertising.guide') }}"><i class="fa-solid fa-rectangle-ad" aria-hidden="true"></i>دليل الإعلانات</a></li>
                            @endif
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ $kfsActive('contact.*', 'requests.*', 'suggestions.*', 'surveys.*', 'complaints.*') ? 'active' : '' }}"
                            href="#" id="contactDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            تواصل معنا<span class="kfs-active-dot" aria-hidden="true"></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="contactDropdown">
                            <li><a class="dropdown-item" href="{{ route('contact.index') }}"><i class="fa-solid fa-headset" aria-hidden="true"></i>الدعم الفني</a></li>
                            <li><a class="dropdown-item" href="{{ route('requests.index') }}"><i class="fa-solid fa-file-pen" aria-hidden="true"></i>تقديم شكوى أو بلاغ</a></li>
                            <li><a class="dropdown-item" href="{{ route('suggestions.create') }}"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i>تقديم مقترح</a></li>
                            <li><a class="dropdown-item" href="{{ route('surveys.service.create') }}"><i class="fa-solid fa-star-half-stroke" aria-hidden="true"></i>تقييم مستوى أداء الخدمات</a></li>
                        </ul>
                    </li>
                </ul>

                {{-- Authentication --}}
                <div class="d-flex align-items-center auth-links">
                    @guest
                        <a href="{{ route('login') }}" class="btn-kfs"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>تسجيل الدخول</a>
                    @else
                        <div class="dropdown">
                            <button type="button" class="kfs-user-toggle dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-circle-user" aria-hidden="true"></i>
                                <span>مرحباً، {{ Str::words(Auth::user()->name, 1, '') }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('citizen.dashboard') }}">
                                        <i class="fa-solid fa-table-columns" aria-hidden="true"></i> لوحة التحكم
                                    </a>
                                </li>
                                @if ($kfsHas('lms.dashboard'))
                                    <li><a class="dropdown-item" href="{{ route('lms.dashboard') }}"><i class="fa-solid fa-book-open-reader" aria-hidden="true"></i> تعلّمي (استدامة)</a></li>
                                @endif
                                @if ($kfsHas('syndicate.my-projects'))
                                    <li><a class="dropdown-item" href="{{ route('syndicate.my-projects') }}"><i class="fa-solid fa-helmet-safety" aria-hidden="true"></i> مشروعاتي (الإشراف الهندسي)</a></li>
                                @endif
                                @if ($kfsHas('market.orders.index'))
                                    <li><a class="dropdown-item" href="{{ route('market.orders.index') }}"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i> طلباتي من المتجر</a></li>
                                @endif
                                @if (auth()->user()->isEmployee())
                                    <li>
                                        <a class="dropdown-item fw-bold" href="{{ route('employee.erp.index') }}">
                                            <i class="fa-solid fa-briefcase" aria-hidden="true"></i> بوابة الموظفين (ERP)
                                        </a>
                                    </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> تسجيل الخروج
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endguest
                </div>
            </div>

            <div class="kfs-nav__actions">
                <button type="button" class="header-search-btn kfs-theme-btn" data-theme-toggle aria-pressed="false"
                    aria-label="تفعيل الوضع الليلي" title="تفعيل الوضع الليلي">
                    <i class="fa-solid fa-moon kfs-theme-btn__moon" aria-hidden="true"></i>
                    <i class="fa-solid fa-sun kfs-theme-btn__sun" aria-hidden="true"></i>
                </button>
                <button type="button" class="header-search-btn" id="searchTrigger" aria-label="البحث في البوابة" title="ابحث في الموقع"
                    aria-controls="searchOverlay">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </button>
                <button class="navbar-toggler kfs-burger d-xl-none" type="button" id="mobileMenuToggler"
                    aria-label="فتح القائمة" aria-controls="mobileMenuPanel" aria-expanded="false">
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </nav>
</header>
<!-- ======================= Header End ======================= -->

<!-- ======================= Mobile Menu Start ======================= -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay" aria-hidden="true"></div>

<div class="mobile-menu-panel" id="mobileMenuPanel" role="dialog" aria-modal="true" aria-label="القائمة الرئيسية" aria-hidden="true">
    <div class="mobile-menu-header">
        <div class="mobile-menu-brand">
            <img src="{{ $kfsLogo }}" alt="" class="mobile-logo" width="42" height="42">
            <span class="mobile-brand-text">محافظة كفر الشيخ</span>
        </div>
        <button type="button" class="mobile-menu-close" id="closeMobileMenu" aria-label="إغلاق القائمة">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    <div class="mobile-menu-user">
        @guest
            <div class="user-greeting">
                <i class="fa-solid fa-circle-user" aria-hidden="true"></i>
                <span>زائر كريم</span>
            </div>
            <div class="user-actions">
                <a href="{{ route('login') }}" class="btn-gold-sm">تسجيل الدخول</a>
                <a href="{{ route('register.citizen') }}" class="btn-outline-gold-sm">حساب جديد</a>
            </div>
        @else
            <div class="user-greeting">
                <i class="fa-solid fa-circle-user" aria-hidden="true"></i>
                <span>مرحباً، <span class="user-name">{{ Str::words(Auth::user()->name, 2, '') }}</span></span>
            </div>
            @if (auth()->user()->isEmployee())
                <div class="user-actions">
                    <a href="{{ route('employee.erp.index') }}" class="btn-gold-sm">
                        <i class="fa-solid fa-briefcase" aria-hidden="true"></i> ERP
                    </a>
                </div>
            @endif
        @endguest
    </div>

    <nav class="mobile-menu-body" aria-label="قائمة الجوال">
        <ul class="mobile-nav-list">
            <li>
                <a href="{{ route('home') }}" class="{{ $kfsActive('home') ? 'active-mobile-link' : '' }}"
                    @if ($kfsActive('home')) aria-current="page" @endif>
                    <i class="fa-solid fa-house" aria-hidden="true"></i> الرئيسية
                </a>
            </li>
            <li class="mobile-dropdown">
                <button type="button" class="mobile-dropdown-trigger" aria-expanded="false">
                    <i class="fa-solid fa-landmark-flag" aria-hidden="true"></i>
                    <span>المحافظة</span>
                    <i class="fa-solid fa-chevron-down dropdown-arrow" aria-hidden="true"></i>
                </button>
                <ul class="mobile-submenu">
                    <li><a href="{{ route('about') }}">عن المحافظة</a></li>
                    <li><a href="{{ route('about.governor') }}">كلمة المحافظ</a></li>
                    <li><a href="{{ route('officials.show', ['role' => 'governor']) }}">المحافظ</a></li>
                    <li><a href="{{ route('officials.show', ['role' => 'deputy-governor']) }}">نائب المحافظ</a></li>
                    <li><a href="{{ route('officials.show', ['role' => 'secretary-general']) }}">السكرتير العام</a></li>
                    <li><a href="{{ route('officials.show', ['role' => 'assistant-secretary-general']) }}">السكرتير العام المساعد</a></li>
                    <li><a href="{{ route('landmarks.index') }}">السياحة والمعالم</a></li>
                    <li><a href="{{ route('investments.index') }}">الاستثمار</a></li>
                </ul>
            </li>
            <li class="mobile-menu-divider" role="presentation"></li>
            <li class="mobile-dropdown">
                <button type="button" class="mobile-dropdown-trigger" aria-expanded="false">
                    <i class="fa-solid fa-hand-holding-hand" aria-hidden="true"></i>
                    <span>الخدمات الإلكترونية</span>
                    <i class="fa-solid fa-chevron-down dropdown-arrow" aria-hidden="true"></i>
                </button>
                <ul class="mobile-submenu">
                    <li><a href="{{ route('services.index') }}"><i class="fa-solid fa-hand-holding-hand" aria-hidden="true"></i> دليل الخدمات</a></li>
                    @if ($kfsHas('gis.index'))
                        <li><a href="{{ route('gis.index') }}"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> بوابة الخدمات الجغرافية</a></li>
                    @endif
                    @if ($kfsHas('site-validity.landing'))
                        <li><a href="{{ route('site-validity.landing') }}"><i class="fa-solid fa-ruler-combined" aria-hidden="true"></i> طلب صلاحية الموقع</a></li>
                    @endif
                    @if ($kfsHas('estidama.index'))
                        <li><a href="{{ route('estidama.index') }}"><i class="fa-solid fa-seedling" aria-hidden="true"></i> برنامج استدامة</a></li>
                    @endif
                    @if ($kfsHas('market.home'))
                        <li><a href="{{ route('market.home') }}"><i class="fa-solid fa-store" aria-hidden="true"></i> المتجر الإلكتروني</a></li>
                    @endif
                    @if ($kfsHas('syndicate.verify.form'))
                        <li><a href="{{ route('syndicate.verify.form') }}"><i class="fa-solid fa-helmet-safety" aria-hidden="true"></i> التحقق من شهادة الإشراف الهندسي</a></li>
                    @endif
                </ul>
            </li>
            <li>
                <a href="{{ route('projects.index') }}" class="{{ $kfsActive('projects.*') ? 'active-mobile-link' : '' }}"
                    @if ($kfsActive('projects.*')) aria-current="page" @endif>
                    <i class="fa-solid fa-diagram-project" aria-hidden="true"></i> المشروعات
                </a>
            </li>
            <li>
                <a href="{{ route('posts.index') }}" class="{{ $kfsActive('posts.*') ? 'active-mobile-link' : '' }}"
                    @if ($kfsActive('posts.*')) aria-current="page" @endif>
                    <i class="fa-solid fa-newspaper" aria-hidden="true"></i> الأخبار
                </a>
            </li>
            <li>
                <a href="{{ route('governorate.map') }}" class="{{ $kfsActive('governorate.map') ? 'active-mobile-link' : '' }}">
                    <i class="fa-solid fa-map" aria-hidden="true"></i> الخريطة التفاعلية
                </a>
            </li>
            <li class="mobile-dropdown">
                <button type="button" class="mobile-dropdown-trigger" aria-expanded="false">
                    <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
                    <span>تواصل معنا</span>
                    <i class="fa-solid fa-chevron-down dropdown-arrow" aria-hidden="true"></i>
                </button>
                <ul class="mobile-submenu">
                    <li><a href="{{ route('directory.index') }}"><i class="fa-solid fa-phone" aria-hidden="true"></i> دليل الهاتف</a></li>
                    <li><a href="{{ route('contact.index') }}"><i class="fa-solid fa-envelope" aria-hidden="true"></i> الدعم الفني</a></li>
                    <li><a href="{{ route('requests.index') }}"><i class="fa-solid fa-file-pen" aria-hidden="true"></i> تقديم شكوى أو بلاغ</a></li>
                    <li><a href="{{ route('suggestions.create') }}"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i> تقديم مقترح</a></li>
                    <li><a href="{{ route('surveys.service.create') }}"><i class="fa-solid fa-star" aria-hidden="true"></i> تقييم الخدمات</a></li>
                </ul>
            </li>

            @auth
                <li class="mobile-menu-divider" role="presentation"></li>
                <li>
                    <a href="{{ route('citizen.dashboard') }}">
                        <i class="fa-solid fa-table-columns" aria-hidden="true"></i> لوحة التحكم
                    </a>
                </li>
                <li>
                    <a href="{{ route('logout') }}" class="text-danger-link"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> تسجيل الخروج
                    </a>
                </li>
            @endauth
        </ul>
    </nav>

    <div class="mobile-menu-footer">
        <div class="social-links">
            <a href="https://www.facebook.com/KafrelsheikhGovernorate" aria-label="فيسبوك" target="_blank" rel="noopener"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
            <a href="https://x.com/kfs_gov" aria-label="إكس (تويتر)" target="_blank" rel="noopener"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></a>
            <a href="https://youtube.com/@kafrelsheikhgovernorate583" aria-label="يوتيوب" target="_blank" rel="noopener"><i class="fa-brands fa-youtube" aria-hidden="true"></i></a>
            <a href="https://whatsapp.com/channel/0029VadcREoLI8YgR28sFz40" aria-label="واتساب" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>
            <a href="https://www.instagram.com/kafr_elsheikh_gov" aria-label="إنستجرام" target="_blank" rel="noopener"><i class="fa-brands fa-instagram" aria-hidden="true"></i></a>
        </div>
        <div class="footer-text">بوابة محافظة كفر الشيخ</div>
    </div>
</div>
<!-- ======================= Mobile Menu End ======================= -->

{{-- ======================= Mobile Bottom Navbar ======================= --}}
<nav class="mobile-bottom-nav" id="mobileBottomNav" aria-label="التنقل السريع">
    <div class="bottom-nav-inner">
        <a href="{{ route('home') }}" class="bottom-nav-item {{ $kfsActive('home') ? 'active' : '' }}"
            @if ($kfsActive('home')) aria-current="page" @endif>
            <span class="nav-icon-wrap"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i></span>
            <span class="nav-label">الرئيسية</span>
        </a>
        <a href="{{ route('posts.index') }}" class="bottom-nav-item {{ $kfsActive('posts.*') ? 'active' : '' }}"
            @if ($kfsActive('posts.*')) aria-current="page" @endif>
            <span class="nav-icon-wrap"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i></span>
            <span class="nav-label">الأخبار</span>
        </a>
        <a href="{{ route('services.index') }}" class="bottom-nav-item {{ $kfsActive('services.*') ? 'active' : '' }}"
            @if ($kfsActive('services.*')) aria-current="page" @endif>
            <span class="nav-icon-wrap"><i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i></span>
            <span class="nav-label">الخدمات</span>
        </a>
        <a href="{{ route('contact.index') }}" class="bottom-nav-item {{ $kfsActive('contact.*') ? 'active' : '' }}"
            @if ($kfsActive('contact.*')) aria-current="page" @endif>
            <span class="nav-icon-wrap"><i class="fa-solid fa-headset" aria-hidden="true"></i></span>
            <span class="nav-label">تواصل</span>
        </a>
        @auth
            <a href="{{ route('citizen.dashboard') }}" class="bottom-nav-item {{ $kfsActive('citizen.dashboard*') ? 'active' : '' }}">
                <span class="nav-icon-wrap"><i class="fa-solid fa-circle-user" aria-hidden="true"></i></span>
                <span class="nav-label">حسابي</span>
            </a>
        @else
            <a href="{{ route('login') }}" class="bottom-nav-item {{ $kfsActive('login') ? 'active' : '' }}">
                <span class="nav-icon-wrap"><i class="fa-solid fa-circle-user" aria-hidden="true"></i></span>
                <span class="nav-label">دخول</span>
            </a>
        @endauth
    </div>
</nav>

{{-- ======================= Search Overlay ======================= --}}
<div class="search-overlay" id="searchOverlay" role="dialog" aria-modal="true" aria-labelledby="searchOverlayTitle" aria-hidden="true">
    <button type="button" class="close-search" id="closeSearch" aria-label="إغلاق البحث">&times;</button>
    <div class="search-container">
        <div class="search-header-text">
            <h2 id="searchOverlayTitle">ما الذي تبحث عنه؟</h2>
            <p>ابحث في أخبار، خدمات، ومشروعات محافظة كفر الشيخ</p>
        </div>
        <form action="{{ route('search') }}" class="search-modal-form" role="search">
            <div class="input-group-premium">
                <label for="searchInput" class="visually-hidden">كلمة البحث</label>
                <input type="search" name="query" placeholder="اكتب كلمة البحث هنا..." autocomplete="off" id="searchInput">
                <button type="submit" class="modal-submit-btn">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span>بحث</span>
                </button>
            </div>
        </form>
        <div class="search-hints mt-4">
            <span>كلمات شائعة:</span>
            <a href="{{ route('search', ['query' => 'الخطة الاستثمارية']) }}">الخطة الاستثمارية</a>
            <a href="{{ route('search', ['query' => 'وظائف']) }}">وظائف</a>
            <a href="{{ route('search', ['query' => 'مبادرة حياة كريمة']) }}">حياة كريمة</a>
        </div>
    </div>
</div>
