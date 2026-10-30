@extends('layouts.app')

@section('title', 'متابعة الطلب والسداد')

@include('payments.partials.styles')

@section('content')
    @php
        $paid = method_exists($payable, 'isPaid') && $payable->isPaid();
        $canPay = method_exists($payable, 'canBePaid') && $payable->canBePaid();
        $amount = (float) $payable->payableAmount();
    @endphp

    <main class="main-content kfs-portal" dir="rtl">
        <x-kfs.page-hero title="متابعة الطلب والسداد" kicker="السداد الإلكتروني" subtitle="ادفع رسوم طلبك بأمان عبر بوابة e-finance الحكومية." icon="fa-solid fa-credit-card" />

        <section class="kfs-pay container" aria-labelledby="pay-state-title">
            <div class="kfs-pay__card" data-reveal>
                @foreach (['error' => 'error', 'info' => 'info', 'success' => 'info'] as $key => $variant)
                    @if (session($key))
                        <div class="kfs-note kfs-pay__alert kfs-pay__alert--{{ $variant }} {{ $variant === 'error' ? 'kfs-note--danger' : 'kfs-note--info' }}" role="{{ $variant === 'error' ? 'alert' : 'status' }}">
                            <i class="fa-solid {{ $variant === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info' }}" aria-hidden="true"></i><div>{{ session($key) }}</div>
                        </div>
                    @endif
                @endforeach

                @if ($paid)
                    <div class="kfs-pay__icon kfs-pay__icon--ok" aria-hidden="true"><i class="fas fa-check"></i></div>
                    <h2 class="kfs-pay__title" id="pay-state-title">تم السداد</h2>
                    <p class="kfs-pay__lead">تم سداد قيمة هذا الطلب بنجاح.</p>
                @elseif ($canPay)
                    <div class="kfs-pay__icon kfs-pay__icon--warn" aria-hidden="true"><i class="fas fa-credit-card"></i></div>
                    <h2 class="kfs-pay__title" id="pay-state-title">بانتظار السداد</h2>
                    <p class="kfs-pay__lead">يمكنكم السداد إلكترونياً بأمان عبر بوابة e-finance الحكومية.</p>
                @else
                    <div class="kfs-pay__icon kfs-pay__icon--info" aria-hidden="true"><i class="fas fa-hourglass-half"></i></div>
                    <h2 class="kfs-pay__title" id="pay-state-title">الطلب قيد المراجعة</h2>
                    <p class="kfs-pay__lead">ستحدد الإدارة قيمة الطلب بعد مراجعته، ثم يمكنكم السداد من هذه الصفحة. احتفظوا بهذا الرابط.</p>
                @endif

                <dl class="kfs-pay__rows">
                    <div class="kfs-pay__row"><dt>البيان</dt><dd>{{ $payable->payableDescription() }}</dd></div>
                    <div class="kfs-pay__row kfs-pay__row--total">
                        <dt>المبلغ المستحق</dt>
                        <dd>{{ $amount > 0 ? number_format($amount, 2) . ' ج.م' : 'لم يُحدد بعد' }}</dd>
                    </div>
                    @if ($lastTransaction)
                        <div class="kfs-pay__row"><dt>آخر محاولة دفع</dt><dd>{{ $lastTransaction->statusLabel() }} — <span dir="ltr">{{ $lastTransaction->created_at->format('Y/m/d h:i A') }}</span></dd></div>
                    @endif
                </dl>

                <div class="kfs-pay__actions">
                    @if ($canPay)
                        <a href="{{ $payUrl }}" class="btn-kfs kfs-pay__btn kfs-pay__btn--primary">
                            <i class="fas fa-lock" aria-hidden="true"></i> ادفع الآن {{ number_format($amount, 2) }} ج.م
                        </a>
                    @endif
                    @if ($paid && $lastTransaction && $lastTransaction->isCompleted())
                        <a href="{{ \Modules\Payments\Http\Controllers\ReceiptController::urlFor($lastTransaction) }}" target="_blank" rel="noopener" class="btn-kfs-outline kfs-pay__btn kfs-pay__btn--ghost">
                            <i class="fas fa-print" aria-hidden="true"></i> طباعة الإيصال <span class="visually-hidden">(يفتح في نافذة جديدة)</span>
                        </a>
                    @endif
                </div>

                <ul class="kfs-pay__trust" aria-label="ضمانات الدفع">
                    <li><i class="fa-solid fa-lock" aria-hidden="true"></i> اتصال مشفّر</li>
                    <li><i class="fa-solid fa-building-columns" aria-hidden="true"></i> بوابة e-finance الحكومية</li>
                    <li><i class="fa-solid fa-receipt" aria-hidden="true"></i> إيصال إلكتروني فوري</li>
                </ul>
            </div>
        </section>
    </main>
@endsection
