@extends('layouts.app')

@section('title', 'بوابة الموظف الرقمية - ERP')

@push('css')
    <style>
        .erp-container {
            min-height: 60vh;
        }

        .erp-sidebar {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #4b6584;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 5px;
            transition: 0.3s;
        }

        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background: #e1b12c;
            color: #fff;
        }

        .sidebar-menu li a i {
            margin-left: 10px;
            font-size: 1.1rem;
        }

        .erp-stat-card {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            border-right: 5px solid #e1b12c;
        }

        .stat-val {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1e272e;
        }

        .stat-label {
            color: #718093;
            font-size: 0.9rem;
        }
    </style>
@endpush

@section('content')
    <x-kfs.page-hero size="compact" kicker="منظومة ERP محافظة كفر الشيخ" current="بوابة الموظف الرقمية"
        :title="'مرحباً بك مجدداً، ' . explode(' ', auth()->user()->name)[0]"
        :subtitle="'لديك ' . $stats['tasks_count'] . ' مهام جديدة تتطلب انتباهك اليوم. نتمنى لك يوماً عملياً موفقاً.'"
        icon="fa-solid fa-briefcase" />

    <div class="container erp-container mb-5">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-12">
                <!-- Placeholder for upcoming table (Transactions) -->
                <div class="p-5 border-dashed text-center rounded-3 bg-white shadow-sm" style="border: 2px dashed #eee;">
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">نظام إدارة المعاملات قيد التطوير</h5>
                    <p class="text-muted small">هذا القسم سيقوم بربط معاملات المواطنين بالمكتب الميداني التابع لك
                        قريباً.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
