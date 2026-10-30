@extends('layouts.app')
@section('title', 'تم إرسال الطلب بنجاح')
@push('css')
    <link rel="stylesheet" href="{{ asset('css/gis-services.css') }}">
@endpush

@section('content')
    <main class="gp-page">
        <x-kfs.page-hero size="compact" title="تم إرسال الطلب بنجاح" kicker="الخدمات المكانية" icon="fa-solid fa-circle-check"
            subtitle="تم تسجيل المعاملة وسداد الرسوم؛ احتفظ برقم المعاملة للمتابعة." :crumbs="['الخدمات المكانية' => route('gis.index')]"
            current="تأكيد الطلب">
            <x-slot:meta>
                <span><i class="fa-solid fa-hashtag" aria-hidden="true"></i> رقم المعاملة: {{ $submission->id }}</span>
                <span><i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $submission->created_at->format('Y/m/d') }}</span>
            </x-slot:meta>
        </x-kfs.page-hero>

        <div class="container">
            <div class="gp-narrow">
                <div class="gp-card gp-receipt">
                    <div class="gp-receipt__top">
                        <div class="gp-receipt__badge" aria-hidden="true"><i class="fa-solid fa-check"></i></div>
                        <h2>تم إرسال طلبكم بنجاح</h2>
                        <p>تم تسجيل المعاملة بنجاح. يرجى الاحتفاظ برقم المعاملة التالي لمتابعة حالة الطلب.</p>
                        <div class="gp-ticket">
                            <div>
                                <small>رقم المعاملة</small>
                                <strong id="gpTicketId">{{ $submission->id }}</strong>
                            </div>
                            <button type="button" onclick="copyTicket()" aria-label="نسخ رقم المعاملة" title="نسخ الرقم"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <div class="gp-receipt__next">
                        <h3>ماذا سيحدث الآن؟</h3>
                        <ol>
                            <li><span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-comment-sms"></i></span><span>ستصلك رسالة SMS تأكيدية خلال لحظات.</span></li>
                            <li><span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-user-check"></i></span><span>يراجع الفريق الفني البيانات والمستندات.</span></li>
                            <li><span class="gp-ico" aria-hidden="true"><i class="fa-solid fa-location-crosshairs"></i></span><span>يتم تحديد موعد للمعاينة الميدانية.</span></li>
                        </ol>
                    </div>
                    <div class="gp-actions">
                        <a href="{{ route('gis.tracking', ['ticket' => $submission->id]) }}" class="gp-btn"><i class="fa-solid fa-magnifying-glass-location" aria-hidden="true"></i> تتبع الطلب</a>
                        <button type="button" onclick="window.print()" class="gp-btn gp-btn--ghost"><i class="fa-solid fa-print" aria-hidden="true"></i> طباعة الإفادة</button>
                        <a href="{{ route('gis.index') }}" class="gp-btn gp-btn--ghost"><i class="fa-solid fa-grip" aria-hidden="true"></i> خدمات أخرى</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        function copyTicket() {
            const id = document.getElementById('gpTicketId').textContent.trim();
            navigator.clipboard?.writeText(id).then(() => {
                if (window.Swal) Swal.fire({ icon: 'success', title: 'تم النسخ', text: 'تم نسخ رقم المعاملة', timer: 2000, showConfirmButton: false });
            });
        }
    </script>
@endpush
