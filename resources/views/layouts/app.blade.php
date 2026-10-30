<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    {{-- ===== 1. Basic meta ===== --}}
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="google" content="notranslate" />
    <link rel="alternate" href="{{ url()->current() }}" hreflang="ar" />

    {{-- ===== 2. SEO ===== --}}
    <title>@yield('title', $settings['site_name'] ?? 'محافظة كفر الشيخ - الموقع الرسمي')</title>
    <meta name="description" content="@yield('description', $settings['site_description'] ?? 'الموقع الرسمي لمحافظة كفر الشيخ، تقدم آخر الأخبار، الخدمات، والمشاريع.')" />
    <meta name="keywords" content="محافظة كفر الشيخ, كفر الشيخ, خدمات حكومية, أخبار كفر الشيخ, استثمار" />
    <meta name="author" content="محافظة كفر الشيخ" />

    {{-- ===== 3. Open Graph / Twitter ===== --}}
    <meta property="og:title" content="@yield('title', $settings['site_name'] ?? 'محافظة كفر الشيخ')" />
    <meta property="og:description" content="@yield('description', $settings['site_description'] ?? 'البوابة الإلكترونية الرسمية لمحافظة كفر الشيخ')" />
    <meta property="og:image" content="@yield('og_image', !empty($settings['site_logo_header']) ? asset(Storage::url($settings['site_logo_header'])) : asset('images/pwa/icon-512.png'))" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:type" content="website" />
    <meta property="og:locale" content="ar_EG" />
    <meta name="twitter:card" content="summary_large_image" />

    {{-- ===== 4. Icons + PWA ===== --}}
    <link rel="icon" href="{{ asset('favicon/favicon.ico') }}" sizes="any" />
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/pwa/icon-192.png') }}" />
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/apple-touch-icon.png') }}" />
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}" />
    <meta name="theme-color" content="#26302D" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="apple-mobile-web-app-title" content="كفر الشيخ" />
    <meta name="application-name" content="بوابة محافظة كفر الشيخ" />
    <meta name="msapplication-TileColor" content="#26302D" />
    <link rel="canonical" href="{{ url()->current() }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="kfs-auth" content="1">
    @endauth
    @php
        // Server-side opt-in for the service worker: only guest, non-sensitive,
        // non-personalised GET pages may be cached for offline reading.
        $kfsPublicCache = auth()->guest()
            && request()->isMethod('GET')
            && !session()->has('errors')
            && empty(session()->getOldInput())
            && !session()->has('success')
            && !session()->has('error')
            && !session()->has('status')
            && !request()->is(
                'login', 'logout', 'register*', 'password*', 'forgot-password', 'reset-password*',
                'two-factor*', 'payments*', 'efinance*', 'receipts*', 'dashboard*', 'citizen*',
                'account*', 'secure-files*', 'store/cart*', 'store/checkout*', 'store/orders*',
                'store/wishlist*', 'store/sell*', 'syndicate*', 'erp-governorate*', 'gis*', 'admin*'
            );
    @endphp
    @if ($kfsPublicCache)
        <meta name="kfs-cache" content="public">
    @endif

    {{-- Colour theme (light / dark) — resolved before any CSS paints so there is no flash.
         Default is LIGHT; dark only when the visitor explicitly chose it (localStorage 'kfs-theme' === 'dark'). The OS setting is not followed.
         Public API: window.KFSTheme.get() / set('light'|'dark'|'auto') / toggle(). --}}
    <script>
        (function () {
            var KEY = 'kfs-theme', r = document.documentElement, saved = null;
            var COLORS = { light: '#26302D', dark: '#101513' };
            try { saved = localStorage.getItem(KEY); } catch (e) {}
            if (saved !== 'light' && saved !== 'dark') saved = null;
            function system() { return 'light'; }
            function paint(t) {
                r.setAttribute('data-theme', t);
                r.setAttribute('data-bs-theme', t);
                r.style.colorScheme = t;
                var metas = document.querySelectorAll('meta[name="theme-color"]');
                for (var i = 0; i < metas.length; i++) metas[i].setAttribute('content', COLORS[t]);
            }
            function sync() {
                var t = r.getAttribute('data-theme'), on = t === 'dark';
                var btns = document.querySelectorAll('[data-theme-toggle]');
                for (var i = 0; i < btns.length; i++) {
                    btns[i].setAttribute('aria-pressed', on ? 'true' : 'false');
                    var label = on ? 'تفعيل الوضع النهاري' : 'تفعيل الوضع الليلي';
                    btns[i].setAttribute('aria-label', label);
                    btns[i].setAttribute('title', label);
                    var txt = btns[i].querySelector('[data-theme-label]');
                    if (txt) txt.textContent = on ? 'الوضع النهاري' : 'الوضع الليلي';
                }
            }
            function set(t, silent) {
                if (t === 'auto') { saved = null; try { localStorage.removeItem(KEY); } catch (e) {} t = system(); }
                else { saved = t === 'dark' ? 'dark' : 'light'; t = saved; try { localStorage.setItem(KEY, t); } catch (e) {} }
                // Brief cross-fade class (CSS disables it under reduced motion / «إيقاف الحركة»).
                r.classList.add('kfs-theme-anim');
                clearTimeout(set.t); set.t = setTimeout(function () { r.classList.remove('kfs-theme-anim'); }, 400);
                paint(t); sync();
                if (!silent && window.KFSA11y && window.KFSA11y.announce) {
                    window.KFSA11y.announce(t === 'dark' ? 'تم تفعيل الوضع الليلي' : 'تم تفعيل الوضع النهاري');
                }
                try { document.dispatchEvent(new CustomEvent('kfs:themechange', { detail: { theme: t } })); } catch (e) {}
                return t;
            }
            paint(saved || system());
            // Another tab changed the theme → follow it.
            window.addEventListener('storage', function (e) {
                if (e.key !== KEY) return;
                saved = (e.newValue === 'light' || e.newValue === 'dark') ? e.newValue : null;
                paint(saved || system()); sync();
            });
            document.addEventListener('click', function (e) {
                var b = e.target.closest && e.target.closest('[data-theme-toggle]');
                if (b) { e.preventDefault(); window.KFSTheme.toggle(); }
            });
            document.addEventListener('DOMContentLoaded', function () { paint(r.getAttribute('data-theme')); sync(); });
            window.KFSTheme = {
                get: function () { return r.getAttribute('data-theme'); },
                isExplicit: function () { return !!saved; },
                set: set,
                toggle: function () { return set(r.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'); },
                sync: sync
            };
        })();
    </script>

    {{-- Apply saved accessibility preferences before first paint (no flash). --}}
    <script>
        (function () {
            try {
                var s = JSON.parse(localStorage.getItem('kfs-a11y-v1') || '{}'), r = document.documentElement;
                ['contrast', 'dyslexia', 'grayscale', 'links', 'cursor', 'noanim', 'guide'].forEach(function (k) {
                    if (s[k]) r.classList.add('kfs-a11y-' + k);
                });
                if (s.font && s.font !== 1) r.style.fontSize = (s.font * 100) + '%';
            } catch (e) {}
        })();
        // Motion bootstrap: content is only ever hidden-before-reveal while <html> has
        // .kfs-motion (JS on + «إيقاف الحركة» off + no prefers-reduced-motion).
        (function () {
            var r = document.documentElement, reduce = false;
            r.classList.add('js');
            try { reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}
            if (!reduce && !r.classList.contains('kfs-a11y-noanim')) r.classList.add('kfs-motion');
            // Safety net: if the motion engine never loads, show everything.
            setTimeout(function () { if (!window.KFSMotion) r.classList.remove('kfs-motion'); }, 3500);
        })();
    </script>

    {{-- ===== 5. Styles ===== --}}
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.rtl.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600&family=Tajawal:wght@400;500;700;800&display=swap"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11.1.15/swiper-bundle.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="{{ asset('css/kfs-ds.css') }}?v={{ @filemtime(public_path('css/kfs-ds.css')) }}" />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ @filemtime(public_path('css/style.css')) }}" />
    <link rel="stylesheet" href="{{ asset('css/index.css') }}?v={{ @filemtime(public_path('css/index.css')) }}" />
    <link rel="stylesheet" href="{{ asset('css/kfs-motion.css') }}?v={{ @filemtime(public_path('css/kfs-motion.css')) }}" />
    <link rel="stylesheet" href="{{ asset('css/kfs-sections.css') }}?v={{ @filemtime(public_path('css/kfs-sections.css')) }}" />
    @stack('css')
    {{-- Unified inner-page hero (after page CSS so legacy rules cannot override it). --}}
    <link rel="stylesheet" href="{{ asset('css/kfs-hero.css') }}?v={{ @filemtime(public_path('css/kfs-hero.css')) }}" />
    {{-- Dark theme layer: last so it can override page CSS (tokens + component fixes). --}}
    <link rel="stylesheet" href="{{ asset('css/kfs-theme.css') }}?v={{ @filemtime(public_path('css/kfs-theme.css')) }}" />

    {{-- SweetAlert2 is loaded early because some views call Swal inline. --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
</head>

<body>
    <a class="kfs-skip-link" href="#main-content">تخطَّ إلى المحتوى الرئيسي</a>
    <div class="kfs-scroll-progress" id="kfsScrollProgress" aria-hidden="true"></div>
    <div class="kfs-page-progress" id="kfsPageProgress" aria-hidden="true"></div>

    {{-- Splash loader: logo + «كفر الشيخ الرقمية». First visit of the session only; skipped for reduced motion. --}}
    <style>
        #page-loader{position:fixed;inset:0;z-index:999999;display:grid;place-items:center;overflow:hidden;color:#26302D;
            background:radial-gradient(120% 90% at 50% 38%,#FFFFFF 0%,#FCFBF7 45%,#F4F2EA 100%);
            transition:clip-path .75s cubic-bezier(.7,0,.2,1),opacity .5s .25s,visibility .75s;clip-path:circle(150% at 50% 42%)}
        #page-loader.hidden{clip-path:circle(0% at 50% 42%);opacity:0;visibility:hidden;pointer-events:none}
        #page-loader .kl-pattern{position:absolute;inset:0;opacity:.12;pointer-events:none;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='56' viewBox='0 0 56 56'%3E%3Cpath d='M28 4 52 28 28 52 4 28Z' fill='none' stroke='%23D8B03E' stroke-width='1.2'/%3E%3Ccircle cx='28' cy='28' r='3' fill='%23D8B03E'/%3E%3C/svg%3E");
            -webkit-mask-image:radial-gradient(closest-side,#000 30%,transparent);mask-image:radial-gradient(closest-side,#000 30%,transparent)}
        #page-loader .kl-inner{position:relative;text-align:center;padding:24px}
        #page-loader .kl-emblem{position:relative;width:176px;height:176px;margin:0 auto 26px;display:grid;place-items:center}
        #page-loader .kl-ring{position:absolute;inset:0;border-radius:50%;
            background:conic-gradient(from 0deg,#D8B03E 0deg,#F0D878 70deg,transparent 140deg,transparent 200deg,#00BFD8 260deg,#39D9E8 320deg,#D8B03E 360deg);
            -webkit-mask:radial-gradient(farthest-side,transparent calc(100% - 4px),#000 calc(100% - 3px));mask:radial-gradient(farthest-side,transparent calc(100% - 4px),#000 calc(100% - 3px));
            animation:kl-spin 2.4s linear infinite}
        #page-loader .kl-ring2{position:absolute;inset:14px;border-radius:50%;border:1px dashed rgba(216,176,62,.45);animation:kl-spin 9s linear infinite reverse}
        #page-loader .kl-plate{position:relative;width:140px;height:140px;display:grid;place-items:center;animation:kl-pop .7s cubic-bezier(.2,.9,.3,1.3) both}
        #page-loader .kl-plate img{width:128px;height:auto;display:block;filter:drop-shadow(0 10px 18px rgba(29,36,34,.18));animation:kl-float 3s ease-in-out .7s infinite}
        #page-loader .kl-title{margin:0;font-family:"Tajawal","IBM Plex Sans Arabic",system-ui,sans-serif;font-weight:800;line-height:1.2;
            font-size:clamp(1.9rem,6vw,2.8rem);letter-spacing:0;
            background:linear-gradient(100deg,#26302D 0%,#26302D 35%,#B88920 48%,#26302D 62%,#26302D 100%);background-size:250% 100%;
            -webkit-background-clip:text;background-clip:text;color:transparent;
            animation:kl-wipe .9s cubic-bezier(.6,0,.2,1) .35s both,kl-shine 2.6s ease-in-out 1.2s infinite}
        #page-loader .kl-title b{color:transparent;-webkit-text-stroke:0}
        #page-loader .kl-wave{display:block;width:230px;height:18px;margin:12px auto 10px}
        #page-loader .kl-wave path{fill:none;stroke-width:2.5;stroke-linecap:round;stroke-dasharray:1;stroke-dashoffset:1;animation:kl-draw 1.1s ease-out .6s forwards}
        #page-loader .kl-wave path+path{animation-delay:.8s}
        #page-loader .kl-sub{margin:0;font-family:"IBM Plex Sans Arabic","Tajawal",system-ui,sans-serif;font-size:.95rem;color:#68645A;animation:kl-fade .6s ease 1s both}
        #page-loader .kl-bar{width:180px;height:3px;margin:22px auto 0;border-radius:3px;background:rgba(29,36,34,.08);overflow:hidden;animation:kl-fade .5s ease 1.1s both}
        #page-loader .kl-bar i{display:block;height:100%;width:40%;border-radius:3px;background:linear-gradient(90deg,#00BFD8,#D8B03E);animation:kl-bar 1.2s cubic-bezier(.6,0,.4,1) infinite}
        @keyframes kl-spin{to{transform:rotate(360deg)}}
        @keyframes kl-pop{from{transform:scale(.6);opacity:0}to{transform:none;opacity:1}}
        @keyframes kl-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
        @keyframes kl-glow{0%,100%{box-shadow:0 0 0 6px rgba(255,253,225,.06),0 18px 50px -12px rgba(0,0,0,.65),0 0 50px -12px rgba(0,191,216,.3)}50%{box-shadow:0 0 0 10px rgba(255,253,225,.04),0 18px 50px -12px rgba(0,0,0,.65),0 0 80px -6px rgba(216,176,62,.45)}}
        @keyframes kl-wipe{from{clip-path:inset(0 0 0 100%);transform:translateY(8px)}to{clip-path:inset(0 0 0 0);transform:none}}
        @keyframes kl-shine{0%{background-position:100% 0}60%,100%{background-position:-50% 0}}
        @keyframes kl-draw{to{stroke-dashoffset:0}}
        @keyframes kl-fade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
        @keyframes kl-bar{0%{transform:translateX(160%)}100%{transform:translateX(-260%)}}
        @media (prefers-reduced-motion:reduce){#page-loader,#page-loader *{animation:none!important;transition:none!important}}
    </style>
    <div id="page-loader" role="status" aria-live="polite">
        <div class="kl-pattern" aria-hidden="true"></div>
        <div class="kl-inner">
            <div class="kl-emblem" aria-hidden="true">
                <span class="kl-ring"></span>
                <span class="kl-ring2"></span>
                <span class="kl-plate">
                    <img src="{{ !empty($settings['site_logo_header']) ? Storage::url($settings['site_logo_header']) : asset('logo.png') }}" alt="" width="104" height="102">
                </span>
            </div>
            <p class="kl-title">كفر الشيخ الرقمية</p>
            <svg class="kl-wave" viewBox="0 0 230 18" aria-hidden="true" focusable="false">
                <path pathLength="1" stroke="#D8B03E" d="M2 9 C30 1 58 1 86 9 S142 17 170 9 S214 3 228 9" />
                <path pathLength="1" stroke="#00BFD8" opacity=".7" d="M2 14 C30 6 58 6 86 14 S142 22 170 14 S214 8 228 14" />
            </svg>
            <p class="kl-sub">البوابة الإلكترونية الرسمية لمحافظة كفر الشيخ</p>
            <div class="kl-bar" aria-hidden="true"><i></i></div>
            <span class="visually-hidden">جارٍ تحميل البوابة…</span>
        </div>
    </div>
    <script>
        (function () {
            var l = document.getElementById('page-loader');
            var seen = false;
            try { seen = sessionStorage.getItem('kfs-loaded') === '1'; sessionStorage.setItem('kfs-loaded', '1'); } catch (e) {}
            var calm = document.documentElement.classList.contains('kfs-a11y-noanim');
            if (seen || calm || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) { l.remove(); return; }
            var start = Date.now(), done = false;
            var hide = function () {
                if (done || !l) return; done = true;
                // keep the splash on screen long enough for its intro animation (~1.4s), never longer than needed
                setTimeout(function () {
                    l.classList.add('hidden');
                    setTimeout(function () { l && l.remove(); l = null; }, 800);
                }, Math.max(0, 1400 - (Date.now() - start)));
            };
            if (document.readyState === 'complete') hide(); else window.addEventListener('load', hide);
            setTimeout(hide, 3500); // safety net: slow assets never block the page
        })();
    </script>

    <div id="page-wrapper">
        @include('includes.header')

        <div id="main-content" tabindex="-1">
            @yield('content')
        </div>

        @include('includes.footer')
    </div>

    {{-- ===== Floating tools: accessibility + voice + back to top ===== --}}
    <div class="kfs-fab-stack">
        <button type="button" id="kfsA11yFab" class="kfs-fab kfs-fab--a11y" aria-label="أدوات الإتاحة (Alt+1)" title="أدوات الإتاحة (Alt+1)" aria-haspopup="dialog">
            <i class="fa-solid fa-universal-access" aria-hidden="true"></i>
        </button>
        <button type="button" id="kfsVoiceBtn" class="kfs-fab kfs-fab--voice" aria-label="تشغيل الأوامر الصوتية (Alt+V)" title="الأوامر الصوتية (Alt+V)" aria-pressed="false">
            <i class="fa-solid fa-microphone" aria-hidden="true"></i>
        </button>
    </div>
    <div class="kfs-voice-status" id="kfsVoiceStatus" role="status" aria-live="polite" hidden></div>
    <button type="button" class="kfs-back-to-top" id="kfsBackToTop" aria-label="العودة إلى أعلى الصفحة" title="أعلى الصفحة">
        <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
    </button>
    <div id="kfsLiveRegion" class="visually-hidden" role="status" aria-live="polite" aria-atomic="true"></div>

    {{-- ===== Sign-language video (toggled from the accessibility panel) ===== --}}
    <div id="signLangPlayer" role="region" aria-label="مترجم لغة الإشارة" hidden>
        <div class="sign-lang-header">
            <span class="sign-lang-title">مترجم لغة الإشارة</span>
            <div class="sign-lang-controls">
                <button type="button" id="signLangToggle" aria-label="تشغيل أو إيقاف الفيديو"><i class="fa-solid fa-play" aria-hidden="true"></i></button>
                <button type="button" id="signLangClose" aria-label="إغلاق مترجم لغة الإشارة"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </div>
        </div>
        <div class="sign-lang-body">
            <video id="signLangVideo" loop muted playsinline preload="none" aria-label="فيديو لغة الإشارة">
                <source src="{{ asset('videos/sign-language.mp4') }}" type="video/mp4">
            </video>
        </div>
        <div class="sign-lang-footer">يعرض ترجمة بلغة الإشارة لمحتوى البوابة</div>
    </div>

    {{-- ===== Voice command route map (built from real route names) ===== --}}
    @php
        $kfsVoiceRouteNames = [
            'home' => 'home', 'services' => 'services.index', 'news' => 'posts.index',
            'complaints' => 'requests.index', 'projects' => 'projects.index', 'investments' => 'investments.index',
            'tourism' => 'landmarks.index', 'estidama' => 'estidama.index', 'contact' => 'contact.index',
            'map' => 'governorate.map', 'store' => 'market.home', 'dashboard' => 'citizen.dashboard',
            'about' => 'about', 'directory' => 'directory.index', 'login' => 'login', 'search' => 'search',
        ];
        $kfsVoiceRoutes = [];
        foreach ($kfsVoiceRouteNames as $kfsKey => $kfsRoute) {
            if (\Illuminate\Support\Facades\Route::has($kfsRoute)) {
                try { $kfsVoiceRoutes[$kfsKey] = route($kfsRoute); } catch (\Throwable $e) { }
            }
        }
    @endphp
    <script type="application/json" id="kfsVoiceRoutes">@json($kfsVoiceRoutes)</script>

    {{-- ===== Scripts ===== --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11.1.15/swiper-bundle.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('js/kfs-a11y.js') }}?v={{ @filemtime(public_path('js/kfs-a11y.js')) }}"></script>
    <script src="{{ asset('js/kfs-voice.js') }}?v={{ @filemtime(public_path('js/kfs-voice.js')) }}" defer></script>
    <script src="{{ asset('js/script.js') }}?v={{ @filemtime(public_path('js/script.js')) }}"></script>
    <script src="{{ asset('js/index.js') }}?v={{ @filemtime(public_path('js/index.js')) }}"></script>
    <script src="{{ asset('js/kfs-motion.js') }}?v={{ @filemtime(public_path('js/kfs-motion.js')) }}"></script>
    @php
        $kfsScope = rtrim(parse_url(url('/'), PHP_URL_PATH) ?? '', '/') . '/';
    @endphp
    <script src="{{ asset('js/pwa.js') }}?v={{ @filemtime(public_path('js/pwa.js')) }}"
        data-sw="{{ asset('sw.js') }}?v={{ @filemtime(public_path('sw.js')) }}"
        data-scope="{{ $kfsScope }}" defer></script>

    {{-- Google Translate (kept; its banner/tooltip chrome is hidden in kfs-ds.css) --}}
    <script>
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'ar',
                includedLanguages: 'ar,en,fr,de,es,zh-CN,ja,ko',
                autoDisplay: false
            }, 'google_translate_element');
            restoreLanguage();
        }

        function restoreLanguage() {
            var m = document.cookie.match(/googtrans=\/[^\/]+\/([^;]+)/);
            if (m && m[1] !== 'ar') {
                var wait = function(cnt) {
                    var sel = document.querySelector('.goog-te-combo');
                    if (sel) {
                        sel.value = m[1];
                        sel.dispatchEvent(new Event('change'));
                    } else if (cnt < 10) {
                        setTimeout(function() { wait(cnt + 1); }, 400);
                    }
                };
                wait(0);
            }
        }

        function setLanguage(lang) {
            document.cookie = "googtrans=/ar/" + lang + "; path=/";
            var trySet = function(cnt) {
                var sel = document.querySelector('.goog-te-combo');
                if (sel) {
                    sel.value = lang;
                    sel.dispatchEvent(new Event('change'));
                } else if (cnt < 10) {
                    setTimeout(function() { trySet(cnt + 1); }, 400);
                } else {
                    location.reload();
                }
            };
            trySet(0);
        }

        (function applyDirectionByLanguage() {
            var match = document.cookie.match(/googtrans=\/[^\/]+\/([^;]+)/);
            var lang = match ? match[1] : 'ar';
            var rtl = lang === 'ar';
            document.documentElement.setAttribute('dir', rtl ? 'rtl' : 'ltr');
            document.documentElement.setAttribute('lang', rtl ? 'ar' : lang);
            document.body.classList.toggle('rtl', rtl);
            document.body.classList.toggle('ltr', !rtl);
        })();
    </script>
    <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit" async></script>

    @stack('scripts')
</body>

</html>
