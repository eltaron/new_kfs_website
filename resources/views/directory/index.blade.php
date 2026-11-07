@extends('layouts.app')
@section('title', 'دليل الهاتف والأرقام الهامة')
@section('description', 'أرقام الطوارئ والشكاوى والوزارات والجهات الخدمية في محافظة كفر الشيخ — ابحث واتصل مباشرة.')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/kfs-pages-info.css') }}?v={{ @filemtime(public_path('css/kfs-pages-info.css')) }}">
@endpush

@section('content')
    @php
        // FA5 class names in the data → FA6 equivalents still render (FA6 ships the v5 aliases).
        $total = $groupedEntries->flatten()->count();
        $slug = fn ($i) => 'kdir-cat-' . $i;
    @endphp
    <main class="kpi">
        <x-kfs.page-hero size="compact" title="دليل الهاتف والأرقام الهامة" kicker="أرقام تهمك" icon="fa-solid fa-address-book"
            subtitle="أرقام الطوارئ والشكاوى والجهات الحكومية — ابحث واتصل بضغطة." />

        <div class="container kpi__wrap">
            @if ($groupedEntries->isNotEmpty())
                {{-- Search + categories --}}
                <div class="kdir-bar">
                    <div class="kdir-search">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <label for="kdirSearch" class="visually-hidden">ابحث باسم الجهة أو الرقم</label>
                        <input type="search" id="kdirSearch" placeholder="ابحث باسم الجهة أو الرقم… (مثال: إسعاف، 122)" autocomplete="off">
                        <span class="kdir-search__count" id="kdirCount">{{ $total }} رقماً</span>
                    </div>
                    <nav class="kdir-cats" aria-label="التصنيفات">
                        <button type="button" class="kdir-cat is-active" data-cat="all">الكل <small>{{ $total }}</small></button>
                        @foreach ($groupedEntries as $category => $entries)
                            <button type="button" class="kdir-cat" data-cat="{{ $slug($loop->index) }}">{{ $category }} <small>{{ $entries->count() }}</small></button>
                        @endforeach
                    </nav>
                </div>

                @foreach ($groupedEntries as $category => $entries)
                    <section class="kdir-section" id="{{ $slug($loop->index) }}" data-section="{{ $slug($loop->index) }}" aria-labelledby="{{ $slug($loop->index) }}-t">
                        <h2 class="kdir-section__title" id="{{ $slug($loop->index) }}-t">{{ $category }} <small>{{ $entries->count() }}</small></h2>
                        <ul class="kdir-grid">
                            @foreach ($entries as $entry)
                                @php $num = trim((string) $entry->phone_number); $tel = preg_replace('/[^\d+]/', '', $num); @endphp
                                <li class="kdir-card" data-search="{{ $entry->name }} {{ $num }} {{ $category }}">
                                    <span class="kdir-card__icon" aria-hidden="true"><i class="{{ $entry->icon_class ?: 'fa-solid fa-phone' }}"></i></span>
                                    <div class="kdir-card__body">
                                        <h3 class="kdir-card__name">{{ $entry->name }}</h3>
                                        <span class="kdir-card__num" dir="ltr">{{ $num }}</span>
                                    </div>
                                    <div class="kdir-card__actions">
                                        <a href="tel:{{ $tel }}" class="kdir-call" aria-label="اتصال بـ{{ $entry->name }}"><i class="fa-solid fa-phone" aria-hidden="true"></i><span>اتصال</span></a>
                                        <button type="button" class="kdir-copy" data-copy="{{ $num }}" aria-label="نسخ رقم {{ $entry->name }}"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach

                <div class="kdir-empty" id="kdirEmpty" hidden>
                    <i class="fa-solid fa-phone-slash" aria-hidden="true"></i>
                    <p>لا توجد نتائج مطابقة. جرّب كلمة أخرى.</p>
                </div>
                <p class="kdir-toast" id="kdirToast" role="status" hidden>تم نسخ الرقم</p>
            @else
                <x-kfs.empty-state icon="phone" title="لا توجد أرقام لعرضها حالياً" />
            @endif
        </div>
    </main>
@endsection

@push('scripts')
<script>
(function () {
    const input = document.getElementById('kdirSearch');
    if (!input) return;
    const norm = s => (s || '').replace(/[ً-ٰٟـ]/g, '').replace(/[أإآٱ]/g, 'ا').replace(/ى/g, 'ي').replace(/ة/g, 'ه').toLowerCase();
    const cards = document.querySelectorAll('.kdir-card');
    const sections = document.querySelectorAll('.kdir-section');
    const cats = document.querySelectorAll('.kdir-cat');
    const count = document.getElementById('kdirCount');
    const empty = document.getElementById('kdirEmpty');
    let cat = 'all';

    function apply() {
        const q = norm(input.value.trim());
        let shown = 0;
        sections.forEach(sec => {
            const inCat = cat === 'all' || sec.dataset.section === cat;
            let secShown = 0;
            sec.querySelectorAll('.kdir-card').forEach(c => {
                const ok = inCat && (!q || norm(c.dataset.search).includes(q));
                c.hidden = !ok; if (ok) secShown++;
            });
            sec.hidden = secShown === 0; shown += secShown;
        });
        count.textContent = shown + (shown === 1 ? ' رقم' : (shown > 10 || shown === 0 ? ' رقماً' : ' أرقام'));
        empty.hidden = shown !== 0;
    }
    input.addEventListener('input', apply);
    cats.forEach(b => b.addEventListener('click', () => {
        cat = b.dataset.cat;
        cats.forEach(x => x.classList.toggle('is-active', x === b));
        apply();
    }));

    const toast = document.getElementById('kdirToast');
    document.querySelectorAll('.kdir-copy').forEach(b => b.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(b.dataset.copy); } catch (e) { return; }
        toast.hidden = false; b.classList.add('is-done');
        setTimeout(() => { toast.hidden = true; b.classList.remove('is-done'); }, 1800);
    }));
})();
</script>
@endpush
