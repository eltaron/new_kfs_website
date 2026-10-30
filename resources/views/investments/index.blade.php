@extends('layouts.app')

@section('title', 'الفرص الاستثمارية')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/investments.css') }}?v={{ @filemtime(public_path('css/investments.css')) }}">
@endpush
@section('content')
    <main class="main-content">
        {{-- Page Header --}}
        <x-kfs.page-hero title="الفرص الاستثمارية في المحافظة" subtitle="اكتشف آفاقًا جديدة للنمو والتطور في مختلف قطاعات المحافظة الواعدة." icon="fa-solid fa-chart-line" :image="asset('images/bg/investment.jpg')" />

        <div class="container pb-5">
            @if ($investments->isNotEmpty())
                <div class="kfs-grid kfs-grid--2" data-stagger>
                    @foreach ($investments as $investment)
                        <a href="{{ route('investments.show', $investment) }}" class="kfs-inv">
                            <span class="kfs-card__media"><img src="{{ $investment->thumbnail ? Storage::url($investment->thumbnail) : asset('images/kfs.png') }}" alt="" loading="lazy"></span>
                            <span class="kfs-card__body">
                                <span><span class="kfs-badge kfs-badge--gold"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> فرصة استثمارية</span></span>
                                <span class="kfs-card__title">{{ $investment->title }}</span>
                                @if ($investment->description)
                                    <span class="kfs-card__text">{{ \Illuminate\Support\Str::limit(trim(html_entity_decode(strip_tags($investment->description), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 120) }}</span>
                                @endif
                                <span class="kfs-card__more">عرض التفاصيل <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
                            </span>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination Links --}}
                <div class="pagination-wrapper mt-5">
                    {{ $investments->links() }}
                </div>
            @else
                <x-kfs.empty-state icon="box" title="لا توجد فرص استثمارية لعرضها حاليًا" />
            @endif
        </div>
        {{-- Investment Map CTA Section --}}
        <section class="pb-5">
            <div class="container">
                <div class="kfs-cta">
                    <div>
                        <span class="kfs-badge kfs-badge--gold mb-2">فرص استثمارية إضافية</span>
                        <h2>هل تبحث عن خريطة أشمل للاستثمار في مصر؟</h2>
                        <p>تصفّح المزيد من الفرص التفاعلية والمشروعات القومية عبر بوابة <strong>خريطة مصر الاستثمارية</strong> الرسمية التابعة لوزارة الاستثمار.</p>
                    </div>
                    <a href="https://www.investinegypt.gov.eg/arabic/pages/ExploreMap.aspx?cat=2&subcat=15" target="_blank" rel="noopener" class="btn-kfs-gold">
                        <span>استكشف الخريطة التفاعلية</span><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>
    </main>
@endsection
