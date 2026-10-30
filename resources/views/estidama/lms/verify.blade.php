@extends('layouts.app')

@section('title', 'التحقق من الشهادات — استدامة')
@section('description', 'تحقق من صحة شهادات برامج مركز استدامة التدريبية الصادرة عن محافظة كفر الشيخ باستخدام الرقم التسلسلي أو رمز QR.')
@include('estidama.lms.partials.learning-assets')

@push('css')
    <meta name="robots" content="noindex">
@endpush

@section('content')
<div class="es-page">
    <x-kfs.page-hero size="compact" title="التحقق من صحة شهادة تدريبية" kicker="خدمة عامة" icon="fa-solid fa-certificate" current="التحقق من الشهادات"
        subtitle="أدخل الرقم التسلسلي المطبوع على الشهادة، أو امسح رمز QR الموجود عليها."
        :crumbs="['استدامة' => route('estidama.index')]" />

    <div class="container es-wrap-900">
        <div class="es-tight">
            <form method="GET" action="{{ route('lms.certificates.verify') }}" class="es-card es-pad" role="search">
                <label for="serial" class="es-card__title"><span class="es-ico" aria-hidden="true"><i class="fa-solid fa-qrcode"></i></span> الرقم التسلسلي للشهادة</label>
                <div class="es-verify-form">
                    <input id="serial" name="serial" class="es-input" dir="ltr" maxlength="40" required
                        pattern="[A-Za-z0-9\-]{6,40}" placeholder="KFS-EST-2026-XXXX-XXXX" value="{{ $serial }}" autocomplete="off">
                    <button type="submit" class="es-btn es-btn--lg"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> تحقق</button>
                </div>
            </form>

            @if ($searched)
                <div aria-live="polite">
                    @if ($certificate && $certificate->isValid())
                        <section class="es-card es-verify-result es-verify-result--valid">
                            <div class="es-verify-result__head">
                                <span class="es-ico es-ico--ok" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                <div>
                                    <h2>شهادة صحيحة وسارية</h2>
                                    <p>صادرة عن محافظة كفر الشيخ — مركز استدامة للتدريب والتطوير.</p>
                                </div>
                            </div>
                            <dl class="es-rows">
                                <div class="es-row"><dt>اسم الحاصل</dt><dd>{{ $certificate->publicHolderName() }}</dd></div>
                                <div class="es-row"><dt>البرنامج</dt><dd>{{ $certificate->program_title }}</dd></div>
                                <div class="es-row"><dt>جهة التدريب</dt><dd>{{ $certificate->trainingProgram?->trainingCenter?->name ?? 'مركز استدامة — محافظة كفر الشيخ' }}</dd></div>
                                <div class="es-row"><dt>تاريخ الإصدار</dt><dd>{{ $certificate->issued_at->format('Y-m-d') }}</dd></div>
                                @if ($certificate->final_score !== null)
                                    <div class="es-row"><dt>الدرجة النهائية</dt><dd>{{ $certificate->final_score }}%</dd></div>
                                @endif
                                <div class="es-row"><dt>الرقم التسلسلي</dt><dd class="es-mono">{{ $certificate->serial }}</dd></div>
                            </dl>
                        </section>
                    @elseif ($certificate)
                        <section class="es-card es-verify-result es-verify-result--invalid">
                            <div class="es-verify-result__head">
                                <span class="es-ico" aria-hidden="true"><i class="fa-solid fa-ban"></i></span>
                                <div>
                                    <h2>هذه الشهادة ملغاة</h2>
                                    <p>أُلغيت هذه الشهادة بتاريخ {{ $certificate->revoked_at->format('Y-m-d') }}. للاستفسار تواصل مع مركز استدامة.</p>
                                </div>
                            </div>
                        </section>
                    @else
                        <section class="es-card es-verify-result es-verify-result--invalid">
                            <div class="es-verify-result__head">
                                <span class="es-ico" aria-hidden="true"><i class="fa-solid fa-circle-xmark"></i></span>
                                <div>
                                    <h2>لم يتم العثور على شهادة</h2>
                                    <p>تأكد من كتابة الرقم التسلسلي كما هو مطبوع على الشهادة (مثال: KFS-EST-2026-ABCD-EFGH).</p>
                                </div>
                            </div>
                        </section>
                    @endif
                </div>
            @endif

            <div class="es-howto">
                <div class="es-card"><span class="es-ico" aria-hidden="true"><i class="fa-solid fa-camera"></i></span><span><strong>امسح رمز QR</strong>يفتح هذه الصفحة مباشرة بنتيجة التحقق.</span></div>
                <div class="es-card"><span class="es-ico" aria-hidden="true"><i class="fa-solid fa-keyboard"></i></span><span><strong>أو اكتب الرقم التسلسلي</strong>المطبوع أسفل الشهادة بالأحرف اللاتينية.</span></div>
                <div class="es-card"><span class="es-ico es-ico--ok" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></span><span><strong>نتيجة موثوقة</strong>تُطابَق البيانات مع سجلات مركز استدامة لحظياً.</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
