@extends('layouts.app')
@section('title', 'الاستيفاء والمتابعة')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ @filemtime(public_path('css/dashboard.css')) }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
@endpush
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#fulfillmentTable').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' },
                order: [[3, 'desc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'الكل']]
            });
        });
    </script>
@endpush
@section('content')
    <x-kfs.page-hero size="compact" title="الاستيفاء — إجراءات مطلوبة" kicker="بوابة المواطن الإلكترونية" icon="fa-solid fa-list-check"
        subtitle="الطلبات التي تحتاج منك استكمال مستندات أو إجراءات." :crumbs="['لوحة التحكم' => route('citizen.dashboard')]" />

    <div class="dashboard-root pt-0">
        <div class="container-fluid g-0">
            <div class="dashboard-layout">
                <main class="dashboard-content-area">
                    @include('citizen.dashboard.partials.top-nav')
                    <div class="px-4">
                        @if ($fulfillmentItems->isNotEmpty())
                            <div class="white-section-card shadow-sm p-4 mb-5">
                                <div class="d-flex align-items-center mb-3 border-bottom pb-2">
                                    <i class="fas fa-tasks text-warning fs-4 ms-2"></i>
                                    <h3 class="m-0 section-head">الطلبات التي تحتاج متابعة</h3>
                                    <span class="badge bg-warning text-dark me-2">{{ $fulfillmentItems->count() }}</span>
                                </div>
                                @include('citizen.dashboard.partials.fulfillment-table')
                            </div>
                        @else
                            <div class="white-section-card shadow-sm p-4 mb-5 text-center py-5">
                                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                <h5 class="text-muted">لا توجد إجراءات مطلوبة حالياً</h5>
                                <p class="text-muted">جميع طلباتك محدثة ولا تحتاج لمتابعة</p>
                            </div>
                        @endif
                    </div>
                </main>
                @include('citizen.dashboard.partials.sidebar', ['activeNav' => 'fulfillment', 'fulfillmentCount' => $fulfillmentItems->count()])
            </div>
        </div>
    </div>
@endsection