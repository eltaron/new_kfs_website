@extends('layouts.app')
@section('title', 'الشكاوي والمقترحات')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ @filemtime(public_path('css/dashboard.css')) }}">
@endpush
@section('content')
    <x-kfs.page-hero size="compact" title="الشكاوي والمقترحات والتواصل" kicker="بوابة المواطن الإلكترونية" icon="fa-solid fa-comments"
        subtitle="تابع شكاواك ومقترحاتك ورسائل التواصل وبلاغات الطوارئ." :crumbs="['لوحة التحكم' => route('citizen.dashboard')]" />

    <div class="dashboard-root pt-0">
        <div class="container-fluid g-0">
            <div class="dashboard-layout">
                <main class="dashboard-content-area">
                    @include('citizen.dashboard.partials.top-nav')
                    <div class="px-4">
                        <div class="white-section-card shadow-sm p-4 mb-5">
                            <ul class="nav nav-tabs-custom mb-3" id="monitoringTabs" role="tablist">
                                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#mon-complaints">شكاوي ({{ $complaints->count() }})</button></li>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mon-suggestions">مقترحات ({{ $suggestions->count() }})</button></li>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mon-contact">رسائل تواصل ({{ $contactMessages->count() }})</button></li>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mon-emergency">بلاغات طوارئ ({{ $emergencyReports->count() }})</button></li>
                            </ul>
                            <div class="tab-content">
                                @include('citizen.dashboard.partials.complaints-tab')
                                @include('citizen.dashboard.partials.suggestions-tab')
                                @include('citizen.dashboard.partials.messages-tab')
                                @include('citizen.dashboard.partials.emergency-tab')
                            </div>
                        </div>
                    </div>
                </main>
                @include('citizen.dashboard.partials.sidebar', ['activeNav' => 'monitoring', 'fulfillmentCount' => $fulfillmentCount ?? 0])
            </div>
        </div>
    </div>
@endsection