@extends('layouts.app')
@section('title', 'الدعم الفني')
@push('css')
    <link rel="stylesheet" href="{{ asset('css') }}/contact.css">
@endpush
@section('content')
    <main class="main-content">
        <x-kfs.page-hero title="الدعم الفني" subtitle="نحن هنا لمساعدتك — أرسل لنا مشكلتك وسنقوم بالرد في أقرب وقت" icon="fa-solid fa-headset" kicker="تواصل معنا" />

        <div class="container py-5">
            <div class="support-wrapper">
                <div class="row g-0">
                    <div class="col-lg-7">
                        <div class="support-form-container">
                            <div class="support-form-header">
                                <i class="fas fa-ticket-alt"></i>
                                <span>فتح تذكرة دعم فني</span>
                            </div>

                            @if (session('success'))
                                <script>Swal.fire({ icon:'success', title:'تم الإرسال', text:{{ json_encode(session('success')) }}, confirmButtonText:'حسناً' });</script>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger">يرجى تصحيح الأخطاء أدناه.</div>
                            @endif

                            <form action="{{ route('contact.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">الاسم الكامل</label>
                                        <input type="text" id="name" name="name" class="form-control"
                                            value="{{ old('name', auth()->check() ? auth()->user()->name : '') }}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">البريد الإلكتروني</label>
                                        <input type="email" id="email" name="email" class="form-control"
                                            value="{{ old('email', auth()->check() ? auth()->user()->email : '') }}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">رقم الهاتف (اختياري)</label>
                                        <input type="tel" id="phone" name="phone" class="form-control"
                                            value="{{ old('phone', auth()->check() ? auth()->user()->phone : '') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="subject" class="form-label">عنوان الطلب</label>
                                        <input type="text" id="subject" name="subject" class="form-control"
                                            value="{{ old('subject') }}" required placeholder="مثال: مشكلة في تسجيل الدخول">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">شرح المشكلة بالتفصيل</label>
                                    <textarea id="message" name="message" class="form-control" rows="6" required placeholder="صِف المشكلة التي تواجهها بأكبر قدر من التفصيل...">{{ old('message') }}</textarea>
                                </div>
                                <button type="submit" class="support-submit-btn">
                                    <i class="fas fa-paper-plane ms-2"></i>
                                    إرسال الطلب
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="support-info-container">
                            <div class="support-brand">
                                <i class="fas fa-life-ring"></i>
                                <h4>فريق الدعم الفني</h4>
                                <p>فريقنا المتخصص جاهز لمساعدتك في حل أي مشكلة تقنية تواجهك</p>
                            </div>
                            <div class="support-channels">
                                <div class="support-channel">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <strong>أوقات العمل</strong>
                                        <span>الأحد - الخميس | 9 صباحاً - 3 مساءً</span>
                                    </div>
                                </div>
                                <div class="support-channel">
                                    <i class="fas fa-reply-all"></i>
                                    <div>
                                        <strong>زمن الاستجابة</strong>
                                        <span>خلال 24 ساعة عمل كحد أقصى</span>
                                    </div>
                                </div>
                                <div class="support-channel">
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <strong>مستوى الأولوية</strong>
                                        <span>يتم تصنيف الطلبات حسب درجة الاستعجال</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
