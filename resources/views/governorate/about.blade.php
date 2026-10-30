@extends('layouts.app')
@section('title', 'عن محافظة كفر الشيخ')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/about-governorate.css') }}">
@endpush

@section('content')
    <main class="about-page">
        {{-- هيرو السكشن --}}
        <x-kfs.page-hero tag="section" title="كفر الشيخ: سلة غذاء مصر" subtitle="حكاية عراقة.. ورؤية مستقبل" kicker="عن المحافظة" icon="fa-solid fa-landmark-flag" :image="asset('images/places/i3.png')" />

        <div class="container py-5">
            {{-- قسم النبذة وسبب التسمية --}}
            <div class="row g-5">
                <div class="col-lg-8">
                    @isset($info['history'])
                        <article class="content-block shadow-sm animate-on-scroll">
                            <div class="block-header">
                                <i class="{{ $info['history']->icon }}"></i>
                                <h3>{{ $info['history']->title }}</h3>
                            </div>
                            <div class="block-body">{!! clean($info['history']->content, 'cms') !!}</div>
                        </article>
                    @endisset

                    @isset($info['naming'])
                        <article class="content-block naming-block mt-4 shadow-sm">
                            <div class="block-header gold-border">
                                <i class="{{ $info['naming']->icon }}"></i>
                                <h3>{{ $info['naming']->title }}</h3>
                            </div>
                            <div class="block-body">{!! clean($info['naming']->content, 'cms') !!}</div>
                        </article>
                    @endisset
                </div>

                <div class="col-lg-4">
                    {{-- سكشن الرؤية والرسالة في سايدبار جانبي مميز --}}
                    @isset($info['vision'])
                        <div class="vision-card mb-4 text-center">
                            <div class="card-icon"><i class="{{ $info['vision']->icon }}"></i></div>
                            <h4>{{ $info['vision']->title }}</h4>
                            <p>{{ strip_tags($info['vision']->content) }}</p>
                        </div>
                    @endisset

                    @isset($info['mission'])
                        <div class="vision-card mission text-center shadow-sm">
                            <div class="card-icon"><i class="{{ $info['mission']->icon }}"></i></div>
                            <h4>{{ $info['mission']->title }}</h4>
                            <p>{{ strip_tags($info['mission']->content) }}</p>
                        </div>
                    @endisset
                </div>
            </div>

            {{-- بماذا تشتهر المحافظة (نظام تبويبات حديث) --}}
            <section class="characteristics-section mt-5 py-4">
                <h2 class="section-title-accent text-center mb-5">ما تمتاز به كفر الشيخ</h2>
                <div class="row g-4 justify-content-center">
                    @php $charKeys = ['natural_resources', 'economic_resources', 'industries']; @endphp
                    @foreach ($charKeys as $key)
                        @isset($info[$key])
                            <div class="col-md-4">
                                <div class="feature-flip-card h-100 shadow-sm">
                                    <div class="icon-holder"><i class="{{ $info[$key]->icon }}"></i></div>
                                    <h5>{{ $info[$key]->title }}</h5>
                                    <div class="feature-text">{!! clean($info[$key]->content, 'cms') !!}</div>
                                </div>
                            </div>
                        @endisset
                    @endforeach
                </div>
            </section>

            {{-- سكشن المشاهير بنظام Accordion أنيق --}}
            <section class="famous-section mt-5">
                <div class="section-heading text-center">
                    <h2>رموز أضاءت تاريخ المحافظة</h2>
                    <p>مبدعون في كل المجالات نفتخر بهم</p>
                </div>

                <div class="accordion accordion-flush famous-accordion mt-4" id="famousAccordion">
                    @foreach ($famousByCat as $category => $people)
                        <div class="accordion-item shadow-sm border-0 mb-3 rounded-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse{{ $loop->index }}">
                                    <span class="cat-dot ms-3"></span> {{ $category }}
                                </button>
                            </h2>
                            <div id="collapse{{ $loop->index }}" class="accordion-collapse collapse"
                                data-bs-parent="#famousAccordion">
                                <div class="accordion-body p-4 bg-white">
                                    <div class="row row-cols-1 row-cols-md-2 g-4">
                                        @foreach ($people as $person)
                                            <div class="col person-row d-flex align-items-start">
                                                @if ($person->image)
                                                    <img src="{{ Str::startsWith($person->image, 'http') ? $person->image : Storage::url($person->image) }}"
                                                        class="p-avatar ms-3">
                                                @else
                                                    <div class="p-avatar-placeholder me-3 text-center pt-2">
                                                        {{ mb_substr($person->name, 0, 1) }}</div>
                                                @endif
                                                <div>
                                                    <h6 class="p-name fw-bold">{{ $person->name }}</h6>
                                                    <p class="p-bio small mb-0">{{ $person->bio }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </main>
@endsection
