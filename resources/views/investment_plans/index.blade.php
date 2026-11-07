@extends('layouts.app')
@section('title', 'الخطة الاستثمارية | محافظة كفر الشيخ')
@section('description', 'أرشيف الخطط الاستثمارية لمحافظة كفر الشيخ وتقاريرها الختامية — عرض وتحميل.')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/kfs-pages-info.css') }}?v={{ @filemtime(public_path('css/kfs-pages-info.css')) }}">
@endpush

@section('content')
    @php
        // Order by the first year in «عام 2023-2024» (the stored string isn't reliably sortable).
        $yearOf = fn ($p) => preg_match('/(\d{4})/u', (string) $p->year_range, $m) ? (int) $m[1] : 0;
        $plans = $investmentPlans->sortByDesc($yearOf)->values();
        $latest = $plans->first();
        $older = $plans->slice(1);
        $label = fn ($p) => trim(preg_replace('/^\s*عام\s*/u', '', (string) $p->year_range));
        $finals = $plans->filter(fn ($p) => $p->final_file_path)->count();
    @endphp

    <main class="kpi">
        <x-kfs.page-hero title="الخطة الاستثمارية" kicker="الإدارة المركزية للتنمية" icon="fa-solid fa-file-invoice-dollar"
            subtitle="الأرشيف الرسمي لخطط المحافظة الاستثمارية وتقاريرها الختامية — للاطلاع والتحميل."
            :image="asset('images/bg/investment-plan.jpg')" />

        <div class="container kpi__wrap">
            @if ($latest)
                <section class="kpi-figs" aria-label="أرقام الأرشيف">
                    <div class="kpi-fig"><strong>{{ $plans->count() }}</strong><span>خطة استثمارية</span></div>
                    <div class="kpi-fig"><strong>{{ $finals }}</strong><span>تقرير ختامي</span></div>
                    <div class="kpi-fig"><strong dir="ltr">{{ $yearOf($plans->last()) }}–{{ $yearOf($latest) + 1 }}</strong><span>نطاق الأرشيف</span></div>
                </section>

                {{-- Latest plan --}}
                <section class="kip-latest" aria-labelledby="kipLatestTitle">
                    <div class="kip-latest__badge"><i class="fa-solid fa-star" aria-hidden="true"></i> أحدث خطة</div>
                    <div class="kip-latest__year" dir="ltr">{{ $label($latest) }}</div>
                    <h2 class="kip-latest__title" id="kipLatestTitle">الخطة الاستثمارية لعام {{ $label($latest) }}</h2>
                    <p class="kip-latest__meta"><i class="fa-regular fa-calendar" aria-hidden="true"></i> نُشرت في {{ $latest->created_at?->format('Y/m/d') }}</p>
                    <div class="kip-docs">
                        <div class="kip-doc">
                            <span class="kip-doc__icon" aria-hidden="true"><i class="fa-solid fa-file-pdf"></i></span>
                            <span class="kip-doc__name">وثيقة الخطة</span>
                            <a href="{{ Storage::url($latest->file_path) }}" target="_blank" rel="noopener" class="kpi-btn kpi-btn--sm"><i class="fa-solid fa-eye" aria-hidden="true"></i><span>عرض</span></a>
                            <a href="{{ Storage::url($latest->file_path) }}" download class="kpi-icon-btn" aria-label="تحميل وثيقة الخطة"><i class="fa-solid fa-download" aria-hidden="true"></i></a>
                        </div>
                        @if ($latest->final_file_path)
                            <div class="kip-doc kip-doc--final">
                                <span class="kip-doc__icon" aria-hidden="true"><i class="fa-solid fa-file-circle-check"></i></span>
                                <span class="kip-doc__name">التقرير الختامي</span>
                                <a href="{{ Storage::url($latest->final_file_path) }}" target="_blank" rel="noopener" class="kpi-btn kpi-btn--sm kpi-btn--olive"><i class="fa-solid fa-eye" aria-hidden="true"></i><span>عرض</span></a>
                                <a href="{{ Storage::url($latest->final_file_path) }}" download class="kpi-icon-btn" aria-label="تحميل التقرير الختامي"><i class="fa-solid fa-download" aria-hidden="true"></i></a>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Archive timeline --}}
                @if ($older->isNotEmpty())
                    <section aria-labelledby="kipArchiveTitle">
                        <x-kfs.section-title id="kipArchiveTitle" title="أرشيف الخطط السابقة" />
                        <ol class="kip-timeline">
                            @foreach ($older as $plan)
                                <li class="kip-row">
                                    <span class="kip-row__year" dir="ltr">{{ $label($plan) }}</span>
                                    <div class="kip-row__card">
                                        <h3 class="kip-row__title">الخطة الاستثمارية لعام {{ $label($plan) }}</h3>
                                        <div class="kip-row__actions">
                                            <a href="{{ Storage::url($plan->file_path) }}" target="_blank" rel="noopener" class="kip-pill"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i> الخطة</a>
                                            @if ($plan->final_file_path)
                                                <a href="{{ Storage::url($plan->final_file_path) }}" target="_blank" rel="noopener" class="kip-pill kip-pill--final"><i class="fa-solid fa-file-circle-check" aria-hidden="true"></i> التقرير الختامي</a>
                                            @endif
                                            <a href="{{ Storage::url($plan->file_path) }}" download class="kpi-icon-btn" aria-label="تحميل خطة {{ $label($plan) }}"><i class="fa-solid fa-download" aria-hidden="true"></i></a>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                <section class="kip-note">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <p>الخطة الاستثمارية تحدد مشروعات المحافظة المموّلة لكل عام مالي، ويوثّق التقرير الختامي ما تم تنفيذه منها.
                        @if (Route::has('projects.index')) تابع أيضاً <a href="{{ route('projects.index') }}">المشروعات القومية</a>@endif
                        @if (Route::has('investments.index')) و<a href="{{ route('investments.index') }}">الفرص الاستثمارية</a>.@endif
                    </p>
                </section>
            @else
                <x-kfs.empty-state icon="file" title="لا توجد خطط منشورة حالياً" />
            @endif
        </div>
    </main>
@endsection
