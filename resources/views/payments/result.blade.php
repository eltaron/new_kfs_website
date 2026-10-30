@extends('layouts.app')

@section('title', 'نتيجة عملية الدفع')

@include('payments.partials.styles')

@push('css')
    @if ($transaction->isPending())
        {{-- The silent callback may arrive a few seconds after the browser returns. --}}
        <meta http-equiv="refresh" content="6">
    @endif
@endpush

@section('content')
    @php
        $state = match ($transaction->status) {
            'completed' => ['ok', 'fa-check', 'تم السداد بنجاح', 'تم تأكيد عملية الدفع من بوابة e-finance.'],
            'pending' => ['info', 'fa-hourglass-half', 'جاري تأكيد عملية الدفع', 'ننتظر تأكيد بوابة الدفع، سيتم تحديث الصفحة تلقائياً خلال ثوانٍ.'],
            'expired' => ['warn', 'fa-clock', 'انتهت صلاحية طلب الدفع', 'يمكنك بدء عملية دفع جديدة.'],
            default => ['bad', 'fa-times', 'لم تكتمل عملية الدفع', 'لم يتم خصم أي مبلغ أو تم رفض العملية من البنك. يمكنك المحاولة مرة أخرى.'],
        };
    @endphp

    <main class="main-content kfs-portal" dir="rtl">
        <x-kfs.page-hero title="نتيجة عملية الدفع" kicker="السداد الإلكتروني" icon="fa-solid fa-receipt" />

        <section class="kfs-pay container" aria-labelledby="pay-state-title">
            <div class="kfs-pay__card" data-reveal>
                @foreach (['error' => 'error', 'info' => 'info'] as $key => $variant)
                    @if (session($key))
                        <div class="kfs-note kfs-pay__alert kfs-pay__alert--{{ $variant }} {{ $variant === 'error' ? 'kfs-note--danger' : 'kfs-note--info' }}" role="{{ $variant === 'error' ? 'alert' : 'status' }}">
                            <i class="fa-solid {{ $variant === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info' }}" aria-hidden="true"></i><div>{{ session($key) }}</div>
                        </div>
                    @endif
                @endforeach

                <div class="kfs-pay__icon kfs-pay__icon--{{ $state[0] }}" aria-hidden="true"><i class="fas {{ $state[1] }}"></i></div>
                <h2 class="kfs-pay__title" id="pay-state-title" role="status">{{ $state[2] }}</h2>
                <p class="kfs-pay__lead">{{ $state[3] }}</p>

                <dl class="kfs-pay__rows">
                    <div class="kfs-pay__row"><dt>البيان</dt><dd>{{ $transaction->description ?? $transaction->typeLabel() }}</dd></div>
                    <div class="kfs-pay__row kfs-pay__row--total"><dt>المبلغ</dt><dd>{{ number_format((float) $transaction->amount, 2) }} ج.م</dd></div>
                    @if ($transaction->collection_fees !== null && (float) $transaction->collection_fees > 0)
                        <div class="kfs-pay__row"><dt>رسوم التحصيل</dt><dd>{{ number_format((float) $transaction->collection_fees, 2) }} ج.م</dd></div>
                    @endif
                    <div class="kfs-pay__row"><dt>رقم العملية</dt><dd dir="ltr">{{ $transaction->sender_request_number }}</dd></div>
                    @if ($transaction->payment_request_number)
                        <div class="kfs-pay__row"><dt>رقم المرجع (e-finance)</dt><dd dir="ltr">{{ $transaction->payment_request_number }}</dd></div>
                    @endif
                    @if ($transaction->authorization_code)
                        <div class="kfs-pay__row"><dt>كود التفويض</dt><dd dir="ltr">{{ $transaction->authorization_code }}</dd></div>
                    @endif
                    <div class="kfs-pay__row"><dt>الحالة</dt><dd><span class="kfs-pill {{ ['ok' => 'success', 'info' => 'info', 'warn' => 'warning', 'bad' => 'danger'][$state[0]] }}">{{ $transaction->statusLabel() }}</span></dd></div>
                    @if ($transaction->completed_at)
                        <div class="kfs-pay__row"><dt>تاريخ السداد</dt><dd dir="ltr">{{ $transaction->completed_at->format('Y/m/d - h:i A') }}</dd></div>
                    @endif
                </dl>

                <div class="kfs-pay__actions">
                    @if ($receiptUrl)
                        <a href="{{ $receiptUrl }}" target="_blank" rel="noopener" class="btn-kfs kfs-pay__btn kfs-pay__btn--primary">
                            <i class="fas fa-print" aria-hidden="true"></i> طباعة الإيصال <span class="visually-hidden">(يفتح في نافذة جديدة)</span>
                        </a>
                    @endif
                    @if ($retryUrl && ! $transaction->isCompleted() && ! $transaction->isPending())
                        <a href="{{ $retryUrl }}" class="btn-kfs kfs-pay__btn kfs-pay__btn--primary">
                            <i class="fas fa-redo" aria-hidden="true"></i> إعادة محاولة الدفع
                        </a>
                    @endif
                    @if ($payable)
                        <a href="{{ $payable->paymentReturnUrl($transaction) }}" class="btn-kfs-outline kfs-pay__btn kfs-pay__btn--ghost">
                            متابعة الطلب <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>

                @if ($transaction->isPending())
                    <p class="kfs-pay__refresh"><i class="fa-solid fa-rotate fa-spin" aria-hidden="true"></i> يتم تحديث الصفحة تلقائياً…</p>
                @endif
            </div>
        </section>
    </main>
@endsection
