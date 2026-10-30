@extends('layouts.app')
@section('title', 'الخدمات المكانية')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ @filemtime(public_path('css/dashboard.css')) }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
@endpush
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#gisTable').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' },
                order: [[2, 'desc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'الكل']]
            });
        });
    </script>
@endpush
@section('content')
    <x-kfs.page-hero size="compact" title="الخدمات المكانية" kicker="بوابة المواطن الإلكترونية" icon="fa-solid fa-map-location-dot"
        subtitle="سجل طلبات الخدمات المكانية وحالة كل طلب." :crumbs="['لوحة التحكم' => route('citizen.dashboard')]" />

    <div class="dashboard-root pt-0">
        <div class="container-fluid g-0">
            <div class="dashboard-layout">
                <main class="dashboard-content-area">
                    @include('citizen.dashboard.partials.top-nav')
                    <div class="px-4">
                        <div class="white-section-card shadow-sm p-4 mb-5">
                            <div class="d-flex align-items-center mb-3 border-bottom pb-2">
                                <i class="fas fa-map-marked-alt text-info fs-4 ms-2"></i>
                                <h3 class="m-0 section-head">سجل طلبات الخدمات المكانية</h3>
                                <span class="badge bg-info text-white me-2">{{ $gisSubmissions->count() }}</span>
                            </div>
                            @include('citizen.dashboard.partials.gis-table')
                        </div>
                    </div>
                </main>
                @include('citizen.dashboard.partials.sidebar', ['activeNav' => 'gis', 'fulfillmentCount' => $fulfillmentCount ?? 0])
            </div>
        </div>
    </div>
@endsection