@extends('layouts.app')
@section('title', 'طلبات صلاحية الموقع — بوابة المواطن')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ @filemtime(public_path('css/dashboard.css')) }}">
    <link rel="stylesheet" href="{{ $svCssUrl }}">
@endpush
@section('content')
    <x-kfs.page-hero size="compact" title="طلبات صلاحية الموقع" kicker="بوابة المواطن الإلكترونية" icon="fa-solid fa-map-location-dot"
        subtitle="تابع حالة طلباتك، مواعيد المعاينة، الاستيفاء المطلوب، والملف النهائي."
        :crumbs="['لوحة التحكم' => route('citizen.dashboard')]" />

    <div class="dashboard-root pt-0">
        <div class="container-fluid g-0">
            <div class="dashboard-layout">
                <main class="dashboard-content-area">
                    @include('citizen.dashboard.partials.top-nav')
                    <div class="px-3 px-md-4 pb-5">
                        @if (session('success'))
                            <div class="sv-alert sv-alert--success" role="status">{{ session('success') }}</div>
                        @endif
                        @if ($pendingCompletion > 0)
                            <div class="sv-alert sv-alert--warning" role="alert">
                                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                لديك {{ $pendingCompletion }} طلب يحتاج استيفاء — افتح الطلب واطلع على المطلوب ثم أرسل الاستيفاء.
                            </div>
                        @endif

                        <div class="sv-card">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <h2 class="sv-card__title m-0"><i class="fa-solid fa-list-check" aria-hidden="true"></i> طلباتي ({{ $requests->total() }})</h2>
                                <a href="{{ route('site-validity.apply') }}" class="sv-btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> طلب جديد</a>
                            </div>

                            @if ($requests->isEmpty())
                                <div class="sv-empty">
                                    <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
                                    <p class="mb-3">لم تقدم أي طلب صلاحية موقع بعد.</p>
                                    <a href="{{ route('site-validity.landing') }}" class="sv-btn sv-btn--ghost">تعرف على الخدمة</a>
                                </div>
                            @else
                                <div class="sv-req-list">
                                    @foreach ($requests as $r)
                                        <article class="sv-req">
                                            <div>
                                                <div class="d-flex flex-wrap align-items-center gap-2">
                                                    <span class="sv-req__num">{{ $r->number }}</span>
                                                    <span class="kfs-badge kfs-badge--{{ \Modules\SiteValidity\Support\SvStatus::webTone($r->status) }}">{{ $r->statusLabel() }}</span>
                                                    @if ($r->paid_at)
                                                        <span class="kfs-badge kfs-badge--success"><i class="fa-solid fa-check" aria-hidden="true"></i> مدفوع</span>
                                                    @endif
                                                </div>
                                                <div class="sv-req__meta">
                                                    <span><i class="fa-solid fa-layer-group" aria-hidden="true"></i> {{ $r->serviceType?->name }}</span>
                                                    <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $r->locationLabel() }}</span>
                                                    <span><i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $r->created_at->format('Y-m-d') }}</span>
                                                    @if ($r->appointment_at && ! $r->isClosed())
                                                        <span><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> المعاينة: {{ $r->appointment_at->format('Y-m-d H:i') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="sv-req__actions">
                                                @if ($r->status === \Modules\SiteValidity\Support\SvStatus::COMPLETION_REQUESTED)
                                                    <a href="{{ route('site-validity.requests.show', $r) }}#completion" class="sv-btn sv-btn--gold"><i class="fa-solid fa-reply" aria-hidden="true"></i> استيفاء</a>
                                                @elseif ($r->canBePaid())
                                                    <a href="{{ $r->paymentUrl() }}" class="sv-btn sv-btn--ghost"><i class="fa-solid fa-credit-card" aria-hidden="true"></i> سداد</a>
                                                @endif
                                                <a href="{{ route('site-validity.requests.show', $r) }}" class="sv-btn"><i class="fa-solid fa-eye" aria-hidden="true"></i> التفاصيل</a>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                                <div class="mt-3">{{ $requests->links() }}</div>
                            @endif
                        </div>
                    </div>
                </main>
                @include('citizen.dashboard.partials.sidebar', ['activeNav' => 'site-validity'])
            </div>
        </div>
    </div>
@endsection
