@extends('layouts.app')
@section('title', 'تم استلام طلبك — ' . \Modules\SiteValidity\Support\SvSettings::get('page_title'))
@push('css')
    <link rel="stylesheet" href="{{ $svCssUrl }}">
@endpush
@section('content')
    <x-kfs.page-hero size="compact" title="تم استلام طلبك" kicker="{{ \Modules\SiteValidity\Support\SvSettings::get('page_title') }}" icon="fa-solid fa-circle-check"
        :crumbs="[\Modules\SiteValidity\Support\SvSettings::get('page_title') => route('site-validity.landing'), 'طلباتي' => route('site-validity.requests.index')]" current="تم الإرسال" />

    <section class="sv-page">
        <div class="container" style="max-width: 760px">
            <div class="sv-card text-center">
                <div class="sv-success-icon" aria-hidden="true"><i class="fa-solid fa-check"></i></div>
                <p class="sv-muted mb-1">رقم الطلب</p>
                <p class="sv-success-num mb-3">{{ $svRequest->number }}</p>
                <p>{{ \Modules\SiteValidity\Support\SvSettings::get('success_message') }}</p>
                <dl class="sv-kv text-start my-4">
                    <div><dt>نوع الخدمة</dt><dd>{{ $svRequest->serviceType?->name }}</dd></div>
                    <div><dt>الموقع</dt><dd>{{ $svRequest->locationLabel() }}</dd></div>
                    <div><dt>الرسوم</dt><dd>{{ number_format((float) $svRequest->fee, 2) }} ج.م</dd></div>
                    <div><dt>تاريخ التقديم</dt><dd>{{ $svRequest->created_at->format('Y-m-d H:i') }}</dd></div>
                </dl>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="{{ route('site-validity.requests.show', $svRequest) }}" class="sv-btn"><i class="fa-solid fa-eye" aria-hidden="true"></i> متابعة الطلب</a>
                    @if ($svRequest->canBePaid())
                        <a href="{{ $svRequest->paymentUrl() }}" class="sv-btn sv-btn--gold"><i class="fa-solid fa-credit-card" aria-hidden="true"></i> سداد الرسوم الآن</a>
                    @endif
                    <a href="{{ route('site-validity.requests.index') }}" class="sv-btn sv-btn--ghost"><i class="fa-solid fa-list-check" aria-hidden="true"></i> طلباتي</a>
                </div>
            </div>
        </div>
    </section>
@endsection
