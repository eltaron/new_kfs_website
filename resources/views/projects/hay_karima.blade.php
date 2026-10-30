@extends('layouts.app')
@section('title', 'مبادرة حياة كريمة - كفر الشيخ')

@push('css')
    <style>
        /* شعار حياة كريمة داخل الهيرو الموحد */
        .hk-logo-top {
            max-width: 150px;
            height: auto;
            filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.3));
        }

        /* كروت القطاعات */
        .sector-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #edf2f7;
            transition: 0.4s;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .sector-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            border-color: #16a085;
        }

        .sector-header {
            background: #f8fbfd;
            padding: 25px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sector-icon {
            width: 60px;
            height: 60px;
            background: #16a085;
            color: #fff;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .sector-body {
            padding: 25px;
        }

        .sector-title {
            font-weight: 800;
            color: #2d3436;
            font-size: 1.4rem;
            margin: 0;
        }

        /* البار الخاص بالنسبة */
        .progress-wrapper {
            background: #f1f2f6;
            padding: 15px;
            border-radius: 12px;
            margin: 20px 0;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 8px;
        }

        .custom-progress {
            height: 8px;
            background: #ddd;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #16a085, #27ae60);
            border-radius: 10px;
        }

        .sector-content {
            color: #636e72;
            line-height: 1.8;
            font-size: 0.95rem;
        }
    </style>
@endpush

@section('content')
    {{-- البانر العلوي (hk-hero: hook kept for the dark-theme :has() rules) --}}
    <x-kfs.page-hero class="hk-hero" title='كفر الشيخ بروح "حياة كريمة"' kicker="المبادرة الرئاسية حياة كريمة"
        subtitle="من تطوير الريف إلى تمكين الإنسان.. نستعرض إنجازات المرحلة الأولى بمركز مطوبس"
        :image="asset('images/bg/projects.jpg')" :crumbs="['المشروعات' => route('projects.index')]">
        <x-slot:actions>
            <img src="{{ asset('images/hayah-karima.png') }}" class="hk-logo-top" alt="شعار مبادرة حياة كريمة">
        </x-slot:actions>
    </x-kfs.page-hero>

    <div class="container pt-0 pb-5">
        <div class="row g-4">
            @foreach ($projects as $item)
                <div class="col-lg-4 col-md-6">
                    <div class="sector-card shadow-sm">
                        <div class="sector-header">
                            <div class="sector-icon shadow-sm"><i class="{{ $item->icon }}"></i></div>
                            <h3 class="sector-title">{{ $item->sector_name }}</h3>
                        </div>

                        <div class="sector-body">
                            {{-- سكشن نسبة الإنجاز --}}
                            <div class="progress-wrapper">
                                <div class="progress-info">
                                    <span class="text-dark">مستوى التنفيذ الميداني</span>
                                    <span class="text-success">{{ $item->progress }}%</span>
                                </div>
                                <div class="custom-progress">
                                    <div class="progress-fill" style="width: {{ $item->progress }}%"></div>
                                </div>
                            </div>

                            <div class="sector-content">
                                {!! clean(Str::limit($item->description, 200), "cms") !!}
                            </div>

                            <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                                <small class="text-muted"><i class="far fa-calendar-alt me-1"></i> التحديث الأخير:
                                    2025</small>
                                <a href="#" class="fw-bold text-success text-decoration-none">التفاصيل <i
                                        class="fas fa-arrow-left fa-xs ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
