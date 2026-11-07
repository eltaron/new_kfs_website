@extends('layouts.app')
@php
    $img = function ($path) {
        if (empty($path)) return null;
        return \Illuminate\Support\Str::startsWith($path, ['http://', 'https://']) ? $path : Storage::url($path);
    };
    $initial = fn ($name) => mb_substr(trim(preg_replace('/^[^\/]*\//u', '', (string) $name)), 0, 1);
    $current = $currentOfficial?->official;
    $roleIcons = [
        'governor' => 'fa-solid fa-landmark-flag',
        'deputy-governor' => 'fa-solid fa-user-tie',
        'secretary-general' => 'fa-solid fa-briefcase',
        'assistant-secretary-general' => 'fa-solid fa-user-gear',
    ];
    $years = $previousOfficials->pluck('start_year')->filter()->map(fn ($y) => (int) $y);
    $firstYear = $years->min();
@endphp
@section('title', $pageTitle . ' | قيادات محافظة كفر الشيخ')
@section('description', ($current ? trim($current->name) . ' — ' : '') . $description . ' كفر الشيخ، والأرشيف التاريخي لمن تولّوا المنصب.')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/kfs-officials.css') }}?v={{ @filemtime(public_path('css/kfs-officials.css')) }}">
@endpush

@section('content')
    <main class="kof">
        <x-kfs.page-hero size="compact" :title="'قيادات المحافظة — ' . $pageTitle" :current="$pageTitle" kicker="قيادات المحافظة"
            :icon="$roleIcons[$roleKey] ?? 'fa-solid fa-user-tie'" :crumbs="['عن المحافظة' => route('about')]" />

        <div class="container kof__wrap">
            {{-- Role switcher --}}
            <nav class="kof-tabs" aria-label="المناصب القيادية">
                @foreach ($roles as $key => $title)
                    <a href="{{ route('officials.show', ['role' => $key]) }}" class="kof-tab {{ $key === $roleKey ? 'is-active' : '' }}"
                        @if ($key === $roleKey) aria-current="page" @endif>
                        <i class="{{ $roleIcons[$key] ?? 'fa-solid fa-user' }}" aria-hidden="true"></i> {{ $title }}
                    </a>
                @endforeach
            </nav>

            {{-- ===== Current official ===== --}}
            @if ($current)
                @php
                    $bio = trim(clean((string) $current->bio, 'cms'));
                    $bio = preg_replace('#<p[^>]*>\s*(&nbsp;|\x{00A0})*\s*</p>#u', '', $bio);
                    $bioLong = mb_strlen(strip_tags($bio)) > 1100;
                    $cImg = $img($current->image);
                @endphp
                <section class="kof-current" aria-labelledby="kofCurrentName">
                    <figure class="kof-current__photo">
                        <div class="kof-current__frame">
                            @if ($cImg)
                                <img src="{{ $cImg }}" alt="{{ $current->name }}"
                                    onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'kof-ph',textContent:'{{ $initial($current->name) }}'}))">
                            @else
                                <span class="kof-ph">{{ $initial($current->name) }}</span>
                            @endif
                        </div>
                    </figure>
                    <div class="kof-current__info">
                        <span class="kof-badge"><span class="kof-badge__dot" aria-hidden="true"></span> {{ $pageTitle }} الحالي</span>
                        <h2 class="kof-current__name" id="kofCurrentName">{{ trim($current->name) }}</h2>
                        <p class="kof-current__role">
                            {{ $description }} كفر الشيخ
                            @if ($currentOfficial->start_year)
                                <span class="kof-current__since"><i class="fa-regular fa-calendar" aria-hidden="true"></i> منذ {{ $currentOfficial->start_year }}</span>
                            @endif
                        </p>
                        @if ($bio !== '')
                            <div class="kof-bio {{ $bioLong ? 'is-collapsed' : '' }}" id="kofBio">
                                <h3 class="kof-bio__title"><i class="fa-solid fa-id-card" aria-hidden="true"></i> السيرة الذاتية</h3>
                                <div class="kof-bio__text">{!! $bio !!}</div>
                            </div>
                            @if ($bioLong)
                                <button type="button" class="kof-more" aria-controls="kofBio" aria-expanded="false" data-more="اقرأ السيرة كاملة" data-less="عرض أقل">
                                    <span>اقرأ السيرة كاملة</span> <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                </button>
                            @endif
                        @endif
                        @if ($roleKey === 'governor' && Route::has('about.governor'))
                            <a href="{{ route('about.governor') }}" class="kof-link">
                                <i class="fa-solid fa-feather-pointed" aria-hidden="true"></i> كلمة المحافظ <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                </section>
            @endif

            {{-- ===== Archive timeline ===== --}}
            @if ($previousOfficials->isNotEmpty())
                <section class="kof-archive" aria-labelledby="kofArchiveTitle">
                    <div class="kof-archive__head">
                        <div>
                            <span class="kof-archive__kicker">الأرشيف التاريخي</span>
                            <h2 class="kof-archive__title" id="kofArchiveTitle">من تولّوا منصب {{ $pageTitle }}</h2>
                            <p class="kof-archive__sub">
                                {{ $previousOfficials->count() }} {{ $previousOfficials->count() > 10 ? 'شخصية' : 'شخصيات' }} أسهمت في مسيرة العطاء بالمحافظة
                                @if ($firstYear) منذ عام {{ $firstYear }} @endif
                            </p>
                        </div>
                        <div class="kof-search">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            <label for="kofSearch" class="visually-hidden">ابحث بالاسم أو السنة</label>
                            <input type="search" id="kofSearch" placeholder="ابحث بالاسم أو السنة…" autocomplete="off">
                        </div>
                    </div>

                    {{-- Decade jump links --}}
                    @if ($byDecade->count() > 1)
                        <nav class="kof-decades" aria-label="الانتقال إلى عقد">
                            @foreach ($byDecade as $decade => $items)
                                <a href="#kof-d{{ $decade }}">{{ $decade ? $decade . 's' : 'غير محدد' }}</a>
                            @endforeach
                        </nav>
                    @endif

                    <div class="kof-timeline" id="kofTimeline">
                        @foreach ($byDecade as $decade => $items)
                            <div class="kof-decade" id="kof-d{{ $decade }}" data-decade>
                                <h3 class="kof-decade__label"><span>{{ $decade ? 'عقد ' . $decade : 'فترة غير محددة' }}</span></h3>
                                <ul class="kof-grid">
                                    @foreach ($items as $role)
                                        @php
                                            $o = $role->official;
                                            $pImg = $img($o?->image);
                                            $period = $role->start_year
                                                ? ($role->end_year && $role->end_year != $role->start_year ? $role->start_year . ' – ' . $role->end_year : $role->start_year)
                                                : '—';
                                        @endphp
                                        <li class="kof-card" data-search="{{ trim($o?->name) }} {{ $role->start_year }} {{ $role->end_year }}">
                                            <figure class="kof-card__photo">
                                                @if ($pImg)
                                                    <img src="{{ $pImg }}" alt="{{ $o?->name }}" loading="lazy"
                                                        onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'kof-ph',textContent:'{{ $initial($o?->name) }}'}))">
                                                @else
                                                    <span class="kof-ph">{{ $initial($o?->name) }}</span>
                                                @endif
                                                <span class="kof-card__period">{{ $period }}</span>
                                            </figure>
                                            <div class="kof-card__body">
                                                <h4 class="kof-card__name">{{ trim($o?->name) }}</h4>
                                                @if ($o?->bio && mb_strlen(strip_tags($o->bio)) < 80)
                                                    <small class="kof-card__note">{{ trim(strip_tags($o->bio)) }}</small>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                    <p class="kof-empty" id="kofEmpty" hidden>لا توجد نتائج مطابقة.</p>
                </section>
            @endif
        </div>
    </main>
@endsection

@push('scripts')
    <script>
    (function () {
        const more = document.querySelector('.kof-more');
        more?.addEventListener('click', () => {
            const box = document.getElementById('kofBio');
            const open = box.classList.toggle('is-collapsed') === false;
            more.setAttribute('aria-expanded', open ? 'true' : 'false');
            more.querySelector('span').textContent = open ? more.dataset.less : more.dataset.more;
        });

        const input = document.getElementById('kofSearch');
        if (!input) return;
        const norm = (s) => (s || '').replace(/[ً-ٰٟـ]/g, '').replace(/[أإآٱ]/g, 'ا').replace(/ى/g, 'ي').replace(/ة/g, 'ه').toLowerCase();
        const cards = document.querySelectorAll('.kof-card');
        const decades = document.querySelectorAll('[data-decade]');
        const empty = document.getElementById('kofEmpty');
        input.addEventListener('input', () => {
            const q = norm(input.value.trim());
            let shown = 0;
            cards.forEach(c => { const ok = !q || norm(c.dataset.search).includes(q); c.hidden = !ok; if (ok) shown++; });
            decades.forEach(d => { d.hidden = !d.querySelector('.kof-card:not([hidden])'); });
            empty.hidden = shown !== 0;
        });
    })();
    </script>
@endpush
