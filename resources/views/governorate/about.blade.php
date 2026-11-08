@extends('layouts.app')
@section('title', 'عن محافظة كفر الشيخ')
@section('description', 'تعرّف على محافظة كفر الشيخ: تاريخها وسبب تسميتها، رؤيتها ورسالتها، مواردها وصناعاتها، ورموزها في كل المجالات.')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/kfs-about.css') }}?v={{ @filemtime(public_path('css/kfs-about.css')) }}">
@endpush

@section('content')
    @php
        $block = fn (string $key) => $info[$key] ?? null;
        $faIcon = fn ($d, $fallback) => trim((string) ($d->icon ?? '')) ?: $fallback;
        $features = collect(['natural_resources' => 'fa-solid fa-leaf', 'economic_resources' => 'fa-solid fa-coins', 'industries' => 'fa-solid fa-industry'])
            ->filter(fn ($icon, $key) => isset($info[$key]));
        $personImg = fn ($p) => $p->image ? (\Illuminate\Support\Str::startsWith($p->image, ['http://', 'https://']) ? $p->image : Storage::url($p->image)) : null;
    @endphp

    <main class="kab">
        <x-kfs.page-hero tag="section" title="كفر الشيخ: سلة غذاء مصر" subtitle="حكاية عراقة… ورؤية مستقبل. محافظة بين النيل والبحر والبحيرة."
            kicker="عن المحافظة" icon="fa-solid fa-landmark-flag" :image="asset('images/places/i3.png')" />

        {{-- ===== Key figures ===== --}}
        @if (count($figures))
            <section class="kab-figures" aria-label="المحافظة في أرقام">
                <div class="container">
                    <div class="kab-figures__grid">
                        @foreach ($figures as $f)
                            <x-kfs.stat-counter :value="$f['value']" :label="$f['label']" :icon="$f['icon']" />
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- ===== Story: history + naming ===== --}}
        @if ($block('history') || $block('naming'))
            <section class="kab-story" aria-labelledby="kabStoryTitle">
                <div class="container">
                    <x-kfs.section-title id="kabStoryTitle" title="الحكاية" subtitle="من أين بدأت كفر الشيخ، ولماذا سُمّيت بهذا الاسم." />

                    <ol class="kab-timeline">
                        @if ($d = $block('history'))
                            <li class="kab-chapter" data-reveal>
                                <span class="kab-chapter__marker" aria-hidden="true"><i class="{{ $faIcon($d, 'fa-solid fa-clock-rotate-left') }}"></i></span>
                                <div class="kab-chapter__card">
                                    <span class="kab-chapter__num">الفصل الأول</span>
                                    <h3 class="kab-chapter__title">{{ $d->title }}</h3>
                                    <div class="kab-prose kab-prose--lead">{!! clean($d->content, 'cms') !!}</div>
                                </div>
                            </li>
                        @endif
                        @if ($d = $block('naming'))
                            <li class="kab-chapter kab-chapter--gold" data-reveal>
                                <span class="kab-chapter__marker" aria-hidden="true"><i class="{{ $faIcon($d, 'fa-solid fa-signature') }}"></i></span>
                                <div class="kab-chapter__card">
                                    <span class="kab-chapter__num">{{ $block('history') ? 'الفصل الثاني' : 'الفصل الأول' }}</span>
                                    <h3 class="kab-chapter__title">{{ $d->title }}</h3>
                                    <div class="kab-prose">{!! clean($d->content, 'cms') !!}</div>
                                </div>
                            </li>
                        @endif
                    </ol>
                </div>
            </section>
        @endif

        {{-- ===== Vision & mission ===== --}}
        @if ($block('vision') || $block('mission'))
            <section class="kab-vm" aria-label="الرؤية والرسالة">
                <div class="container">
                    <div class="kab-vm__grid">
                        @foreach (['vision' => ['fa-solid fa-eye', 'إلى أين نمضي'], 'mission' => ['fa-solid fa-bullseye', 'كيف نعمل']] as $key => [$icon, $kicker])
                            @if ($d = $block($key))
                                <article class="kab-vm__card kab-vm__card--{{ $key }}" data-reveal>
                                    <span class="kab-vm__icon" aria-hidden="true"><i class="{{ $faIcon($d, $icon) }}"></i></span>
                                    <span class="kab-vm__kicker">{{ $kicker }}</span>
                                    <h2 class="kab-vm__title">{{ $d->title }}</h2>
                                    <div class="kab-vm__text">{!! clean($d->content, 'cms') !!}</div>
                                </article>
                            @endif
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- ===== What the governorate is known for ===== --}}
        @if ($features->isNotEmpty())
            <section class="kab-features" aria-labelledby="kabFeaturesTitle">
                <div class="container">
                    <x-kfs.section-title id="kabFeaturesTitle" title="ما تمتاز به كفر الشيخ" subtitle="موارد طبيعية واقتصادية وصناعات تصنع هوية المحافظة." />
                    <div class="kab-features__grid">
                        @foreach ($features as $key => $icon)
                            @php $d = $info[$key]; @endphp
                            <article class="kab-feature" data-reveal>
                                <span class="kab-feature__num" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="kab-feature__icon" aria-hidden="true"><i class="{{ $faIcon($d, $icon) }}"></i></span>
                                <h3 class="kab-feature__title">{{ $d->title }}</h3>
                                @php
                                    // Plain-text content written as "مقدمة: • بند: شرح • بند: شرح" → intro + bulleted list.
                                    $raw = (string) $d->content;
                                    $parts = (! preg_match('/<(ul|ol|li)/i', $raw) && str_contains($raw, '•'))
                                        ? array_values(array_filter(array_map('trim', explode('•', strip_tags($raw))), 'strlen'))
                                        : null;
                                    $intro = $parts && ! str_starts_with(ltrim(strip_tags($raw)), '•') ? array_shift($parts) : null;
                                @endphp
                                <div class="kab-prose kab-prose--sm">
                                    @if ($parts)
                                        @if ($intro)<p>{{ $intro }}</p>@endif
                                        <ul class="kab-points">
                                            @foreach ($parts as $pt)
                                                @php [$lbl, $txt] = str_contains($pt, ':') ? array_map('trim', explode(':', $pt, 2)) : [null, $pt]; @endphp
                                                <li>@if ($lbl)<strong>{{ $lbl }}:</strong> @endif{{ $txt }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        {!! clean($d->content, 'cms') !!}
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- ===== Famous people (tabbed gallery) ===== --}}
        @if ($famousByCat->isNotEmpty())
            @php $allCount = $famousByCat->flatten()->count(); @endphp
            <section class="kab-people" aria-labelledby="kabPeopleTitle">
                <div class="container">
                    <x-kfs.section-title id="kabPeopleTitle" title="رموز أضاءت تاريخ المحافظة" subtitle="مبدعون من أبناء كفر الشيخ في كل المجالات، نفتخر بهم." />

                    <div class="kab-people__bar">
                        <div class="kab-tabs" role="tablist" aria-label="مجالات الرموز">
                            <button type="button" class="kab-tab is-active" role="tab" aria-selected="true" data-cat="all">
                                الكل <small>{{ $allCount }}</small>
                            </button>
                            @foreach ($famousByCat as $category => $people)
                                <button type="button" class="kab-tab" role="tab" aria-selected="false" data-cat="c{{ $loop->index }}">
                                    {{ $category }} <small>{{ $people->count() }}</small>
                                </button>
                            @endforeach
                        </div>
                        <div class="kab-people__search">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            <label for="kabPeopleSearch" class="visually-hidden">ابحث بالاسم</label>
                            <input type="search" id="kabPeopleSearch" placeholder="ابحث بالاسم…" autocomplete="off">
                        </div>
                    </div>

                    <ul class="kab-people__grid" id="kabPeopleGrid" role="tabpanel" aria-live="polite">
                        @foreach ($famousByCat as $category => $people)
                            @php $catIndex = $loop->index; @endphp
                            @foreach ($people as $person)
                                @php $img = $personImg($person); @endphp
                                <li class="kab-person" data-cat="c{{ $catIndex }}" data-name="{{ $person->name }}">
                                    <figure class="kab-person__fig">
                                        @if ($img)
                                            <img src="{{ $img }}" alt="{{ $person->name }}" loading="lazy"
                                                onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'kab-person__initial',textContent:this.alt.trim().charAt(0)}))">
                                        @else
                                            <span class="kab-person__initial" aria-hidden="true">{{ mb_substr(trim($person->name), 0, 1) }}</span>
                                        @endif
                                    </figure>
                                    <div class="kab-person__body">
                                        <h3 class="kab-person__name">{{ $person->name }}</h3>
                                        <span class="kab-person__cat">{{ $category }}</span>
                                        @if ($person->bio)
                                            <p class="kab-person__bio">{{ $person->bio }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        @endforeach
                    </ul>
                    <p class="kab-people__empty" id="kabPeopleEmpty" hidden>لا توجد نتائج مطابقة.</p>
                </div>
            </section>
        @endif

        {{-- ===== Continue exploring ===== --}}
        <section class="kab-next" aria-label="استكشف المزيد">
            <div class="container">
                <div class="kab-next__grid">
                    @foreach ([
                        ['governorate.map', 'fa-solid fa-map-location-dot', 'الخريطة التفاعلية', 'المعالم والمشروعات والخدمات على خريطة واحدة'],
                        ['landmarks.index', 'fa-solid fa-monument', 'السياحة والمعالم', 'اكتشف أجمل الأماكن في المحافظة'],
                        ['about.governor', 'fa-solid fa-user-tie', 'كلمة المحافظ', 'رسالة من محافظ كفر الشيخ'],
                    ] as [$route, $icon, $title, $desc])
                        @if (Route::has($route))
                            <a href="{{ route($route) }}" class="kab-next__card">
                                <span class="kab-next__icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                                <span class="kab-next__body"><strong>{{ $title }}</strong><small>{{ $desc }}</small></span>
                                <i class="fa-solid fa-arrow-left kab-next__go" aria-hidden="true"></i>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
    (function () {
        // (scroll reveal for [data-reveal] is handled site-wide by kfs-motion.js)
        // Famous people: category tabs + name search.
        const grid = document.getElementById('kabPeopleGrid');
        if (!grid) return;
        const tabs = document.querySelectorAll('.kab-tab');
        const people = grid.querySelectorAll('.kab-person');
        const search = document.getElementById('kabPeopleSearch');
        const empty = document.getElementById('kabPeopleEmpty');
        const norm = (s) => (s || '').replace(/[ً-ٰٟـ]/g, '').replace(/[أإآٱ]/g, 'ا').replace(/ى/g, 'ي').replace(/ة/g, 'ه').trim();
        let cat = 'all';

        function apply() {
            const q = norm(search.value);
            let shown = 0;
            people.forEach(p => {
                const ok = (cat === 'all' || p.dataset.cat === cat) && (!q || norm(p.dataset.name).includes(q));
                p.hidden = !ok;
                if (ok) shown++;
            });
            empty.hidden = shown !== 0;
        }
        tabs.forEach(t => t.addEventListener('click', () => {
            cat = t.dataset.cat;
            tabs.forEach(x => { const on = x === t; x.classList.toggle('is-active', on); x.setAttribute('aria-selected', on ? 'true' : 'false'); });
            apply();
        }));
        search.addEventListener('input', apply);
    })();
    </script>
@endpush
