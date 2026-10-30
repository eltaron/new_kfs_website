@extends('layouts.app')

@section('title', 'تم إرسال الطلب بنجاح')

@section('content')
<style>
    .success-card {
        background: #fff;
        border-radius: 24px;
        padding: 50px 40px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
    }

    .success-icon {
        font-size: 90px;
        color: #22c55e;
        margin-bottom: 20px;
    }

    .success-card h2 {
        font-weight: 700;
        color: #1f2937;
    }

    .ref-box {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 16px;
        padding: 24px 30px;
        margin-top: 28px;
        text-align: right;
    }

    .ref-box .ref-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #dcfce7;
        font-size: 0.95rem;
    }

    .ref-box .ref-row:last-child {
        border-bottom: none;
    }

    .ref-box .ref-label {
        color: #6b7280;
        font-weight: 500;
    }

    .ref-box .ref-value {
        color: #111827;
        font-weight: 700;
        font-family: monospace;
        font-size: 1rem;
        letter-spacing: 0.5px;
    }

    .badge-paid {
        background: #dcfce7;
        color: #15803d;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
</style>

<x-kfs.page-hero size="compact" title="تم إرسال الطلب بنجاح" kicker="الخدمات الإلكترونية" icon="fa-solid fa-circle-check"
    subtitle="تم استلام طلبك وسيتم مراجعته من قبل المختصين في أقرب وقت." :crumbs="['الخدمات' => route('services.index')]">
    <x-slot:meta>
        <span><i class="fa-solid fa-hashtag" aria-hidden="true"></i> رقم الطلب: {{ $submission->id }}</span>
    </x-slot:meta>
</x-kfs.page-hero>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="success-card">

                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>

                <h2>تم الدفع بنجاح 🎉</h2>
                <p class="text-muted mt-2">
                    تم استلام طلبك وسيتم مراجعته من قبل المختصين في أقرب وقت.
                </p>

                {{-- تفاصيل المعاملة --}}
                <div class="ref-box">

                    {{-- رقم المرجع الرئيسي --}}
                    @if($submission->payment_request_number)
                    <div class="ref-row">
                        <span class="ref-label">رقم المرجع</span>
                        <span class="ref-value">{{ $submission->payment_request_number }}</span>
                    </div>
                    @endif

                    {{-- كود التفويض --}}
                    @if($submission->authorization_code)
                    <div class="ref-row">
                        <span class="ref-label">كود التفويض</span>
                        <span class="ref-value">{{ $submission->authorization_code }}</span>
                    </div>
                    @endif

                    {{-- رقم الطلب الداخلي --}}
                    <div class="ref-row">
                        <span class="ref-label">رقم الطلب</span>
                        <span class="ref-value">#{{ $submission->id }}</span>
                    </div>

                    {{-- المبلغ --}}
                    <div class="ref-row">
                        <span class="ref-label">المبلغ المدفوع</span>
                        <span class="ref-value">{{ number_format($submission->total_amount, 2) }} ج.م</span>
                    </div>

                    {{-- تاريخ الدفع --}}
                    <div class="ref-row">
                        <span class="ref-label">تاريخ الدفع</span>
                        <span class="ref-value">
                            {{ $submission->paid_at?->format('Y/m/d - h:i A') ?? now()->format('Y/m/d - h:i A') }}
                        </span>
                    </div>

                    {{-- الحالة --}}
                    <div class="ref-row">
                        <span class="ref-label">الحالة</span>
                        <span class="badge-paid">✓ مدفوع</span>
                    </div>

                </div>

                <p class="text-muted mt-3" style="font-size:0.85rem;">
                    احتفظ برقم المرجع للمتابعة
                </p>

                <a href="{{ route('services.index') }}" class="btn btn-primary mt-3 px-5">
                    العودة إلى الخدمات
                </a>

            </div>
        </div>
    </div>
</div>
@endsection