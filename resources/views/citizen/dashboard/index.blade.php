@extends('layouts.app')
@section('title', 'لوحة التحكم')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ @filemtime(public_path('css/dashboard.css')) }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endpush

@section('content')
    <x-kfs.page-hero size="compact" :title="'أهلاً بك، ' . explode(' ', $user->name)[0]" current="لوحة التحكم"
        kicker="بوابة المواطن الإلكترونية" icon="fa-solid fa-table-columns" subtitle="تابع طلباتك وشكاواك ومدفوعاتك من مكان واحد." />

    <div class="dashboard-root pt-0">
        <div class="container-fluid g-0">
            <div class="dashboard-layout">
                <main class="dashboard-content-area">
                    @include('citizen.dashboard.partials.top-nav')
                    <div class="px-4">
                        @include('citizen.dashboard.partials.stats-cards')

                        @php $fulfillmentItems = $fulfillmentItems->take(5); @endphp
                        @include('citizen.dashboard.partials.fulfillment-table')
                        @if ($fulfillmentItems->isNotEmpty())
                        <div class="text-center mb-4">
                            <a href="{{ route('citizen.dashboard.fulfillment') }}" class="btn btn-outline-gold btn-sm rounded-pill">عرض الكل ←</a>
                        </div>
                        @endif

                        @php
                            $serviceSubmissions = $serviceSubmissions->take(5);
                            $gisSubmissions = $gisSubmissions->take(5);
                            $enrollments = $enrollments->take(5);
                            $trainingApplications = $trainingApplications->take(5);
                        @endphp
                        <div class="white-section-card shadow-sm p-4 mb-5">
                            <div class="d-flex align-items-center mb-3 border-bottom pb-2">
                                <i class="fas fa-file-invoice-dollar text-warning fs-4 ms-2"></i>
                                <h3 class="m-0 section-head">طلباتي المقدمة</h3>
                            </div>
                            <ul class="nav nav-tabs-custom mb-3" id="submissionsTabs" role="tablist">
                                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-services">خدمات عامة ({{ $stats['services'] }})</button></li>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-gis">خدمات مكانية ({{ $stats['gis'] }})</button></li>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-estidama">استدامة ({{ $stats['estidama'] }})</button></li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="tab-services">
                                    @include('citizen.dashboard.partials.services-table')
                                    <div class="text-center mt-3">
                                        <a href="{{ route('citizen.dashboard.services') }}" class="btn btn-outline-gold btn-sm rounded-pill">عرض كل الخدمات العامة ←</a>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tab-gis">
                                    @include('citizen.dashboard.partials.gis-table')
                                    <div class="text-center mt-3">
                                        <a href="{{ route('citizen.dashboard.gis') }}" class="btn btn-outline-gold btn-sm rounded-pill">عرض كل الخدمات المكانية ←</a>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tab-estidama">
                                    @include('citizen.dashboard.partials.estidama-table')
                                    <div class="text-center mt-3">
                                        <a href="{{ route('citizen.dashboard.estidama') }}" class="btn btn-outline-gold btn-sm rounded-pill">عرض كل برامج استدامة ←</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @php
                            $complaints = $complaints->take(5);
                            $suggestions = $suggestions->take(5);
                            $contactMessages = $contactMessages->take(5);
                            $emergencyReports = $emergencyReports->take(5);
                        @endphp
                        <div class="white-section-card shadow-sm p-4 mb-5">
                            <div class="d-flex align-items-center mb-3 border-bottom pb-2">
                                <i class="fas fa-comments text-primary fs-4 ms-2"></i>
                                <h3 class="m-0 section-head">المراقبة — الشكاوي والمقترحات والتواصل</h3>
                            </div>
                            <ul class="nav nav-tabs-custom mb-3" id="monitoringTabs" role="tablist">
                                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#mon-complaints">شكاوي ({{ $stats['complaints'] }})</button></li>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mon-suggestions">مقترحات ({{ $stats['suggestions'] }})</button></li>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mon-contact">رسائل تواصل ({{ $stats['contactMessages'] }})</button></li>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mon-emergency">بلاغات طوارئ ({{ $stats['emergencyReports'] }})</button></li>
                            </ul>
                            <div class="tab-content">
                                @include('citizen.dashboard.partials.complaints-tab')
                                @include('citizen.dashboard.partials.suggestions-tab')
                                @include('citizen.dashboard.partials.messages-tab')
                                @include('citizen.dashboard.partials.emergency-tab')
                            </div>
                            <div class="text-center mt-3">
                                <a href="{{ route('citizen.dashboard.monitoring') }}" class="btn btn-outline-gold btn-sm rounded-pill">عرض الكل ←</a>
                            </div>
                        </div>
                        @include('citizen.dashboard.partials.chart')
                    </div>
                </main>
                @include('citizen.dashboard.partials.sidebar', ['activeNav' => 'overview', 'fulfillmentCount' => $fulfillmentCount])
            </div>
        </div>
    </div>
@endsection
