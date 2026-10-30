@php
    // Standalone error layout: no DB, no shared partials — it must render even when the app is broken (500/503).
    $code = trim($__env->yieldContent('code')) ?: '500';
    $homeUrl = url('/');
    $searchUrl = Route::has('search') ? route('search') : null;
    $links = array_filter([
        ['الخدمات الإلكترونية', 'fa-solid fa-hand-holding-heart', Route::has('services.index') ? route('services.index') : null],
        ['الأخبار', 'fa-regular fa-newspaper', Route::has('posts.index') ? route('posts.index') : null],
        ['دليل الهاتف', 'fa-solid fa-address-book', Route::has('directory.index') ? route('directory.index') : null],
        ['تواصل معنا', 'fa-solid fa-headset', Route::has('contact.index') ? route('contact.index') : null],
    ], fn ($l) => $l[2]);
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#1D2422">
    <title>{{ $code }} · @yield('title') — بوابة محافظة كفر الشيخ</title>
    <link rel="icon" href="{{ asset('favicon/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600&family=Tajawal:wght@700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script>
        (function () {
            var t = null;
            try { t = localStorage.getItem('kfs-theme'); } catch (e) {}
            if (t !== 'dark') t = 'light'; // light is the default; dark only when the visitor chose it
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    <style>
        :root {
            --bg: #FCFBF7; --bg2: #F4F2EA; --card: #FFFFFF; --text: #282724; --soft: #68645A; --line: rgba(29, 36, 34, .12);
            --primary: #00788A; --primary-h: #005F6D; --gold: #D8B03E; --gold-d: #B88920; --dark: #26302D; --dark2: #1D2422;
            --accent: var(--tone, #00788A);
            color-scheme: light;
        }
        :root[data-theme="dark"] {
            --bg: #141A18; --bg2: #1A211F; --card: #1E2624; --text: #ECE8D6; --soft: #B9B4A0; --line: rgba(255, 253, 225, .12);
            --primary: #2BC4DA; --primary-h: #5FD6E6; color-scheme: dark;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            min-height: 100vh; display: flex; flex-direction: column;
            font-family: "IBM Plex Sans Arabic", "Tajawal", system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif;
            color: var(--text);
            background:
                radial-gradient(70% 60% at 85% 0%, color-mix(in srgb, var(--accent) 12%, transparent) 0%, transparent 70%),
                radial-gradient(60% 50% at 0% 100%, rgba(216, 176, 62, .12) 0%, transparent 70%),
                var(--bg);
        }
        a { color: var(--primary); }
        .top { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 18px clamp(16px, 4vw, 48px); }
        .brand { display: inline-flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text); font-family: "Tajawal", sans-serif; font-weight: 800; }
        .brand img { width: 46px; height: 46px; object-fit: contain; background: #FFFDE1; border-radius: 12px; padding: 4px; box-shadow: 0 0 0 1px var(--line); }
        .brand small { display: block; font-weight: 600; font-size: .78rem; color: var(--soft); font-family: "IBM Plex Sans Arabic", sans-serif; }
        .theme { width: 42px; height: 42px; border-radius: 12px; border: 1px solid var(--line); background: var(--card); color: var(--text); cursor: pointer; font-size: 1rem; }
        .theme:focus-visible, .btn:focus-visible, .chip:focus-visible, .search input:focus-visible { outline: 3px solid var(--gold); outline-offset: 2px; }

        main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 8px 16px 40px; min-width: 0; }
        .card {
            position: relative; width: min(760px, 100%); text-align: center;
            padding: clamp(28px, 5vw, 52px) clamp(20px, 5vw, 56px) clamp(26px, 4vw, 40px);
            background: var(--card); border-radius: 24px; border: 1px solid var(--line);
            box-shadow: 0 30px 70px -40px rgba(29, 36, 34, .45); overflow: hidden;
        }
        .card::before { content: ""; position: absolute; inset-inline: 0; top: 0; height: 5px; background: linear-gradient(90deg, var(--gold), var(--accent)); }

        /* Big code with water filling the digits */
        .code {
            position: relative; margin: 0 auto; width: fit-content;
            font-family: "Playfair Display", Georgia, serif; font-weight: 700; line-height: .95;
            font-size: clamp(5.5rem, 22vw, 10rem); letter-spacing: .02em; direction: ltr;
            color: color-mix(in srgb, var(--accent) 10%, transparent); -webkit-text-stroke: 2px color-mix(in srgb, var(--accent) 55%, transparent);
        }
        .code span {
            position: absolute; inset: 0; -webkit-text-stroke: 0;
            color: var(--accent);
            -webkit-mask:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='40' viewBox='0 0 200 40'%3E%3Cpath d='M0 20 C25 6 50 6 75 20 S125 34 150 20 S190 10 200 20 V40 H0Z'/%3E%3C/svg%3E") 0 38% / 200px 40px repeat-x,
                linear-gradient(#000, #000) 0 100% / 100% 50% no-repeat;
                    mask:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='40' viewBox='0 0 200 40'%3E%3Cpath d='M0 20 C25 6 50 6 75 20 S125 34 150 20 S190 10 200 20 V40 H0Z'/%3E%3C/svg%3E") 0 38% / 200px 40px repeat-x,
                linear-gradient(#000, #000) 0 100% / 100% 50% no-repeat;
            animation: water 3s linear infinite;
        }
        @keyframes water { to { -webkit-mask-position: 200px 38%, 0 100%; mask-position: 200px 38%, 0 100%; } }

        .waves { display: block; width: 260px; max-width: 80%; height: 20px; margin: -8px auto 18px; }
        .waves path { fill: none; stroke-width: 3; stroke-linecap: round; }

        .badge { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .85rem; border-radius: 999px; font-size: .8rem; font-weight: 700;
            color: var(--accent); background: color-mix(in srgb, var(--accent) 12%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent); }
        h1 { margin: .9rem 0 .5rem; font-family: "Tajawal", sans-serif; font-weight: 800; font-size: clamp(1.5rem, 4vw, 2.1rem); line-height: 1.3; }
        .msg { margin: 0 auto; max-width: 52ch; color: var(--soft); line-height: 1.9; }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 26px; }
        .btn { display: inline-flex; align-items: center; gap: .55rem; min-height: 48px; padding: .7rem 1.3rem; border-radius: 999px; font-weight: 700; font-family: "Tajawal", sans-serif; font-size: 1rem; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background-color .2s, color .2s, border-color .2s, transform .2s; }
        .btn-primary { background: var(--primary); color: #fff; }
        :root[data-theme="dark"] .btn-primary { color: #0E1413; }
        .btn-primary:hover { background: var(--primary-h); transform: translateY(-1px); }
        .btn-ghost { background: transparent; color: var(--text); border-color: var(--line); }
        .btn-ghost:hover { border-color: var(--primary); color: var(--primary); }
        .search { display: flex; gap: 8px; max-width: 460px; margin: 22px auto 0; }
        .search input { flex: 1; min-width: 0; min-height: 48px; padding: 0 1rem; border-radius: 999px; border: 1px solid var(--line); background: var(--bg); color: var(--text); font: inherit; }
        .search button { min-height: 48px; width: 48px; border-radius: 50%; border: 0; background: var(--gold); color: #282724; cursor: pointer; }
        .links { margin-top: 26px; padding-top: 20px; border-top: 1px dashed var(--line); }
        .links p { margin: 0 0 10px; font-size: .85rem; color: var(--soft); }
        .chips { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; }
        .chip { display: inline-flex; align-items: center; gap: .45rem; padding: .45rem .9rem; border-radius: 999px; background: var(--bg2); color: var(--text); text-decoration: none; font-size: .9rem; font-weight: 600; border: 1px solid var(--line); }
        .chip i { color: var(--primary); }
        .chip:hover { border-color: var(--primary); color: var(--primary); }
        .ref { margin-top: 18px; font-size: .78rem; color: var(--soft); direction: rtl; }
        footer { text-align: center; padding: 16px; font-size: .8rem; color: var(--soft); }
        @media (prefers-reduced-motion: reduce) { .code span { animation: none !important; } .btn { transition: none; } }
        @media (max-width: 480px) { .actions .btn { flex: 1 1 100%; justify-content: center; } }
    </style>
</head>
<body style="--tone: @yield('tone', '#00788A')">
    <header class="top">
        <a class="brand" href="{{ $homeUrl }}">
            <img src="{{ asset('logo.png') }}" alt="" width="46" height="46">
            <span>بوابة محافظة كفر الشيخ<small>كفر الشيخ الرقمية</small></span>
        </a>
        <button type="button" class="theme" id="themeBtn" aria-label="تبديل الوضع الليلي/النهاري"><i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i></button>
    </header>

    <main>
        <section class="card" aria-labelledby="errTitle">
            <div class="code" aria-hidden="true">{{ $code }}<span>{{ $code }}</span></div>
            <svg class="waves" viewBox="0 0 260 20" aria-hidden="true" focusable="false">
                <path stroke="#D8B03E" d="M2 8 C35 1 68 1 101 8 S167 15 200 8 S245 3 258 8" />
                <path stroke="var(--accent)" opacity=".6" d="M2 15 C35 8 68 8 101 15 S167 22 200 15 S245 10 258 15" />
            </svg>
            <span class="badge"><i class="@yield('icon', 'fa-solid fa-circle-info')" aria-hidden="true"></i> خطأ {{ $code }}</span>
            <h1 id="errTitle">@yield('heading')</h1>
            <p class="msg">@yield('message')</p>

            <div class="actions">
                @hasSection('actions')
                    @yield('actions')
                @else
                    <a class="btn btn-primary" href="{{ $homeUrl }}"><i class="fa-solid fa-house" aria-hidden="true"></i> العودة للرئيسية</a>
                    <button type="button" class="btn btn-ghost" onclick="history.length > 1 ? history.back() : location.assign('{{ $homeUrl }}')"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> الصفحة السابقة</button>
                @endif
            </div>

            @if ($searchUrl && trim($__env->yieldContent('search')) !== 'off')
                <form class="search" action="{{ $searchUrl }}" method="GET" role="search">
                    <label for="errSearch" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)">ابحث في البوابة</label>
                    <input id="errSearch" type="search" name="query" placeholder="ابحث عن خدمة أو خبر أو جهة…">
                    <button type="submit" aria-label="بحث"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
                </form>
            @endif

            @if (count($links))
                <nav class="links" aria-label="روابط سريعة">
                    <p>أو انتقل مباشرةً إلى:</p>
                    <div class="chips">
                        @foreach ($links as [$label, $icon, $href])
                            <a class="chip" href="{{ $href }}"><i class="{{ $icon }}" aria-hidden="true"></i> {{ $label }}</a>
                        @endforeach
                    </div>
                </nav>
            @endif

            @if (in_array($code, ['500', '503'], true))
                <p class="ref">الوقت: {{ now()->format('Y-m-d H:i') }} — إذا تكررت المشكلة يُرجى التواصل مع الدعم الفني.</p>
            @endif
        </section>
    </main>

    <footer>© {{ date('Y') }} محافظة كفر الشيخ — جميع الحقوق محفوظة</footer>

    <script>
        document.getElementById('themeBtn').addEventListener('click', function () {
            var r = document.documentElement, t = r.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            r.setAttribute('data-theme', t);
            try { localStorage.setItem('kfs-theme', t); } catch (e) {}
        });
    </script>
</body>
</html>
