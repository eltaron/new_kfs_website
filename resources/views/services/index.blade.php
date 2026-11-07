@extends('layouts.app')

@section('title', 'خدمات المحافظة')
@section('content')
    <main class="main-content">
        {{-- Page Header --}}
        <x-kfs.page-hero title="جميع الخدمات" subtitle="مجموعة شاملة من الخدمات الموجهة لدعم مواطني المحافظة." icon="fa-solid fa-hand-holding-hand" :image="asset('images/bg/services.jpg')" />

        <section class="kfs-sec pt-0">
            <div class="container">
                @if ($services->isNotEmpty())
                    <div class="kfs-grid kfs-grid--4" data-stagger>
                        @foreach ($services as $service)
                            <a href="{{ $service->publicUrl() }}"
                                @if ($service->opensExternally()) target="_blank" rel="noopener" @endif
                                class="kfs-svc {{ $service->is_highlighted ? 'kfs-svc--gold' : '' }}">
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
                                        <span class="kfs-svc__desc">{{ \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($service->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 110) }}</span>
                                    @endif
                                </span>
                                <span class="kfs-card__more">ابدأ الخدمة <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <x-kfs.empty-state icon="box" title="لا توجد خدمات منشورة حالياً" text="ستظهر هنا خدمات المحافظة فور إتاحتها." />
                @endif
            </div>
        </section>
    </main>
@endsection
