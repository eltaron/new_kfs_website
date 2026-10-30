@extends('layouts.app')

@section('title', 'مشروعات الخطة الاستثمارية')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/investments.css') }}">
@endpush

@section('content')
    <main class="main-content bg-light">
        {{-- Header --}}
        <x-kfs.page-hero title="مشروعات الخطة الاستثمارية" subtitle="الأرشيف الرسمي لخطط وإنجازات محافظة كفر الشيخ" kicker="الإدارة المركزية للتنمية" icon="fa-solid fa-file-invoice" :image="asset('images/bg/investment-plan.jpg')" />

        <div class="container py-5">
            <div class="row g-5">
                {{-- اليمين: عرض ملفات الخطة --}}
                <div class="col-lg-8">
                    <div class="plans-display-grid">
                        @foreach ($investmentPlans as $plan)
                            <div class="plan-doc-card shadow-sm border-0">
                                <div class="doc-icon">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div class="doc-info">
                                    <h3>الخطة الاستثمارية لعام {{ $plan->year_range }}</h3>
                                    <p>تم الرفع بتاريخ: {{ $plan->created_at->format('Y/m/d') }}</p>
                                    <div class="btn-group mt-3">
                                        <a href="{{ Storage::url($plan->file_path) }}" target="_blank"
                                            class="btn btn-outline-dark btn-sm">عرض الخطة <i class="fas fa-eye"></i></a>
                                        @if ($plan->final_file_path)
                                            <a href="{{ Storage::url($plan->final_file_path) }}" target="_blank"
                                                class="btn btn-outline-success btn-sm ms-2">التقرير الختامي <i
                                                    class="fas fa-check-double"></i></a>
                                        @endif
                                    </div>
                                </div>
                                <a href="{{ Storage::url($plan->file_path) }}" download class="download-arrow">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- اليسار: معلومات وتطبيق شارك --}}
                <div class="col-lg-4">
                    <aside class="sticky-sidebar">
                        {{-- تطبيق شارك كرت مميز --}}
                        <div class="sharek-cta-card">
                            <img src="{{ asset('images/frame.png') }}" width="100%" class="app-logo" alt="تطبيق شارك">
                            <h4>شارك في رسم مستقبلك</h4>
                            <p>تابع المشروعات في منطقتك وقدم مقترحاتك عبر تطبيق شارك 2030 الرسمي.</p>
                            <div class="app-store-links">
                                <a href="#"><img src="{{ asset('images/apple-logo.png') }}" alt="App Store"></a>
                                <a href="#"><img src="{{ asset('images/google-play.png') }}" alt="Google Play"></a>
                            </div>
                        </div>

                        {{-- أرقام سريعة --}}
                        <div class="quick-info-widget mt-4">
                            <h5>إحصائيات الخطة</h5>
                            <ul class="list-unstyled">
                                <li><span>إجمالي الخطط المرفوعة:</span> <strong>{{ $investmentPlans->count() }}</strong>
                                </li>
                                <li><span>نطاق الأرشيف:</span> <strong>{{ $investmentPlans->last()->year_range }} -
                                        {{ $investmentPlans->first()->year_range }}</strong></li>
                            </ul>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </main>
@endsection
