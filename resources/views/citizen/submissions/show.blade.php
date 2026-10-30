@extends('layouts.app')
@section('title', 'تفاصيل الطلب #' . $submission->id)

@push('css')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ @filemtime(public_path('css/dashboard.css')) }}">
    <style>
        @media print {
            .kfs-page-hero, .dash-actions, .dash-back { display: none !important; }
            .kfs-detail { display: block; }
        }
    </style>
@endpush

@section('content')
    @php
        $statusLabels = [
            'pending' => 'قيد المراجعة',
            'awaiting_payment' => 'بانتظار الدفع',
            'in_progress' => 'قيد التنفيذ',
            'paid' => 'تم الدفع',
            'completed' => 'تم التنفيذ',
            'rejected' => 'مرفوض',
        ];
        $steps = [
            ['تم إرسال الطلب إلكترونياً', true, $submission->created_at->format('Y/m/d')],
            ['جاري فحص المستندات مالياً وفنياً', in_array($submission->status, ['awaiting_payment', 'paid', 'completed']), null],
            ['تأكيد عملية الدفع الرقمي', in_array($submission->status, ['paid', 'completed']), null],
            ['تم تنفيذ الخدمة وإصدار المخرجات', $submission->status == 'completed', null],
        ];
        $currentStep = collect($steps)->search(fn ($s) => !$s[1]);
    @endphp

    <x-kfs.page-hero :title="'رقم الطلب: #' . $submission->id" icon="fa-solid fa-file-invoice"
        :kicker="$submission->service->title ?? 'طلب خدمة'" :crumbs="['لوحة التحكم' => route('citizen.dashboard')]" />

    <div class="dashboard-root pt-0">
        <div class="container">
            <div class="mb-3 dash-back">
                <a href="{{ route('citizen.dashboard') }}" class="btn-kfs-outline btn-kfs--sm">
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i> العودة للوحة التحكم
                </a>
            </div>

            <div class="kfs-detail">
                <div class="kfs-detail__main">
                    <section class="kfs-panel" aria-labelledby="subDataTitle">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom">
                            <h2 class="h5 fw-bold m-0" id="subDataTitle"><i class="fa-solid fa-list-check text-primary ms-1" aria-hidden="true"></i> بيانات النموذج المقدم</h2>
                            <span class="badge-status-{{ $submission->status }}">{{ $statusLabels[$submission->status] ?? $submission->status }}</span>
                        </div>
                        <div class="dash-detail-grid">
                            @foreach ($submission->submitted_data as $key => $value)
                                @php
                                    // محاولة إيجاد الاسم العربي من فورم فيلدز لو كان موجوداً
                                    $fieldInfo = collect($submission->service->form_fields)->firstWhere('name', $key);
                                    $label = $fieldInfo['label'] ?? $key;
                                @endphp
                                <div class="dash-detail-item">
                                    <span>{{ $label }}</span>
                                    {{-- التحقق لو كانت البيانات ملف يتم عرض لينك --}}
                                    @if (is_string($value) && (strpos($value, 'http') === 0 || strpos($value, 'files/') === 0))
                                        <a href="{{ Storage::url($value) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary mt-1">
                                            <i class="fa-solid fa-paperclip" aria-hidden="true"></i> مشاهدة المرفق</a>
                                    @else
                                        <strong>{{ is_array($value) ? implode(', ', $value) : $value }}</strong>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if ($submission->admin_notes)
                            <div class="msg-box admin mt-4 mb-0">
                                <label><i class="fa-solid fa-bullhorn ms-1" aria-hidden="true"></i> ملاحظات الإدارة</label>
                                <div class="reply-content">{!! clean($submission->admin_notes) !!}</div>
                            </div>
                        @endif
                    </section>
                </div>

                <aside class="kfs-aside">
                    <section class="kfs-panel" aria-labelledby="subTrackTitle">
                        <h2 class="kfs-panel__title" id="subTrackTitle"><i class="fa-solid fa-route" aria-hidden="true"></i> تتبع الحالة</h2>
                        <ol class="dash-timeline">
                            @foreach ($steps as $i => $step)
                                <li class="{{ $step[1] ? 'is-done' : ($i === $currentStep ? 'is-current' : '') }}">
                                    <strong>{{ $step[0] }}</strong>
                                    <small>{{ $step[1] ? ($step[2] ?? 'مكتمل') : ($i === $currentStep ? 'المرحلة الحالية' : 'لم تبدأ بعد') }}</small>
                                </li>
                            @endforeach
                        </ol>
                    </section>

                    {{-- خيارات الطباعة والدفع --}}
                    <section class="kfs-panel dash-actions" aria-label="إجراءات الطلب">
                        <div class="d-grid gap-2">
                            @foreach (['error' => 'danger', 'info' => 'info', 'success' => 'success'] as $flashKey => $flashClass)
                                @if (session($flashKey))
                                    <div class="alert alert-{{ $flashClass }} mb-2">{{ session($flashKey) }}</div>
                                @endif
                            @endforeach
                            @if ($submission->canBePaid())
                                <a href="{{ route('payments.pay', ['type' => 'service_submission', 'id' => $submission->id]) }}" class="btn-kfs">
                                    <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
                                    <span>سدد الآن {{ number_format((float) $submission->total_amount, 2) }} ج.م</span></a>
                            @endif
                            @if ($submission->fulfillment_action === 'payment' && $submission->fulfillment_status === 'requested' && ($fee = $submission->openFulfillmentFee()))
                                <a href="{{ route('payments.pay', ['type' => 'fulfillment_fee', 'id' => $fee->id]) }}" class="btn btn-danger">
                                    <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
                                    سداد رسوم الاستيفاء {{ number_format((float) $fee->amount, 2) }} ج.م</a>
                            @endif
                            @if ($submission->completedTransaction)
                                <a href="{{ route('receipt.print', $submission->completedTransaction) }}" target="_blank" rel="noopener" class="btn btn-outline-success">
                                    <i class="fa-solid fa-receipt" aria-hidden="true"></i> طباعة إيصال السداد</a>
                            @endif
                            <button type="button" onclick="window.print()" class="btn-kfs-outline">
                                <i class="fa-solid fa-print" aria-hidden="true"></i> طباعة ملخص الطلب
                            </button>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
@endsection
