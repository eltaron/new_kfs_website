@extends('layouts.app')
@section('title', 'تتبع المعاملة - ' . $submission->id)
@push('css')
    <link rel="stylesheet" href="{{ asset('css/gis-services.css') }}">
@endpush

@php
    $status = $submission->status;
    $reviewDone = in_array($status, ['processing', 'completed'], true);
    [$statusLabel, $statusTone] = match ($status) {
        'completed' => ['مكتمل', 'ok'],
        'processing' => ['قيد المراجعة الفنية', 'info'],
        'rejected' => ['مرفوض', 'wait'],
        default => ['تم الاستلام', 'wait'],
    };
@endphp

@section('content')
    <main class="gp-page">
        <x-kfs.page-hero size="compact" title="تتبع حالة الطلب" :kicker="$submission->subService->name ?? 'الخدمات المكانية'"
            icon="fa-solid fa-magnifying-glass-location" :crumbs="['الخدمات المكانية' => route('gis.index')]">
            <x-slot:meta>
                <span><i class="fa-solid fa-hashtag" aria-hidden="true"></i> رقم المعاملة: {{ $submission->id }}</span>
                <span><i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $submission->created_at->format('Y/m/d') }}</span>
            </x-slot:meta>
        </x-kfs.page-hero>

        <div class="container">
            <div class="gp-narrow">
                <div class="gp-card">
                    <div class="gp-track__head">
                        <h2><i class="fa-solid fa-id-card" aria-hidden="true"></i> {{ $submission->subService->name ?? 'طلب خدمة مكانية' }}</h2>
                        <span class="gp-status gp-status--{{ $statusTone }}"><i class="fa-solid fa-circle" style="font-size:.5rem" aria-hidden="true"></i> {{ $statusLabel }}</span>
                    </div>

                    <div class="gp-facts">
                        <div class="gp-fact"><small>رقم المعاملة</small><strong>{{ $submission->id }}</strong></div>
                        <div class="gp-fact"><small>تاريخ التقديم</small><strong>{{ $submission->created_at->format('Y/m/d') }}</strong></div>
                        <div class="gp-fact"><small>العنوان</small><strong>{{ $submission->address_info['details'] ?? 'محافظة كفر الشيخ' }}</strong></div>
                    </div>

                    <ol class="gp-timeline" aria-label="مسار المعاملة">
                        <li class="gp-tl is-done">
                            <span class="gp-tl__dot" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                            <div>
                                <h3>تم استلام الطلب والملحقات</h3>
                                <p>اكتمل تسجيل بياناتك وإرسالها لموظف المتغيرات المكانية.</p>
                            </div>
                        </li>
                        <li class="gp-tl {{ $reviewDone ? 'is-done' : ($status === 'rejected' ? '' : 'is-active') }}">
                            <span class="gp-tl__dot" aria-hidden="true"><i class="fa-solid {{ $reviewDone ? 'fa-check' : 'fa-magnifying-glass' }}"></i></span>
                            <div>
                                <h3>المراجعة والتدقيق التخطيطي</h3>
                                @if ($status === 'received')
                                    <p>الطلب بانتظار بدء الفحص الهندسي والتقني من قِبل الفريق المختص.</p>
                                @elseif ($status === 'rejected')
                                    <p>تم رفض الطلب — راجع الملاحظات أدناه.</p>
                                @else
                                    <p>الطلب قيد المراجعة الفنية الآن.</p>
                                @endif
                            </div>
                        </li>
                        <li class="gp-tl {{ $status === 'completed' ? 'is-done' : ($status === 'processing' ? 'is-active' : '') }}">
                            <span class="gp-tl__dot" aria-hidden="true"><i class="fa-solid {{ $status === 'completed' ? 'fa-check' : 'fa-file-shield' }}"></i></span>
                            <div>
                                <h3>إصدار المحررات المؤمنة والشهادات</h3>
                                <p>عند اكتمال المراجعة تظهر المخرجات في حسابك ويصلك إشعار بذلك.</p>
                            </div>
                        </li>
                    </ol>

                    @if ($submission->admin_notes)
                        <div class="gp-note">
                            <h3><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> ملاحظات هامة</h3>
                            <div class="gp-prose">{!! clean($submission->admin_notes) !!}</div>
                        </div>
                    @endif

                    <div class="gp-actions">
                        @auth
                            <a href="{{ route('citizen.dashboard.gis') }}" class="gp-btn"><i class="fa-solid fa-list-check" aria-hidden="true"></i> طلباتي</a>
                        @endauth
                        <a href="{{ route('gis.index') }}#gp-track" class="gp-btn gp-btn--ghost"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> تتبع طلب آخر</a>
                        <a href="{{ route('home') }}" class="gp-btn gp-btn--ghost"><i class="fa-solid fa-house" aria-hidden="true"></i> الرئيسية</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
