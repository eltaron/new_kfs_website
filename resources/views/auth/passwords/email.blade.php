@extends('layouts.app')
@section('title', 'إعادة تعيين كلمة المرور')
<x-kfs.portal-assets />
@section('content')
    <main class="main-content kfs-portal">
        <x-kfs.page-hero size="compact" title="استعادة كلمة المرور" kicker="أمان الحساب" subtitle="أدخل بريدك الإلكتروني المسجل لاستلام رابط آمن لإعادة التعيين." icon="fa-solid fa-key" :image="asset('images/bg/register.jpg')" :crumbs="['تسجيل الدخول' => route('login')]" />

        <div class="container">
            <div class="kfs-auth__shell kfs-auth__shell--narrow" data-reveal>
                <x-kfs.auth-aside title="استعادة الوصول إلى حسابك" text="سنرسل إلى بريدك الإلكتروني المسجل رابطًا آمنًا صالحًا لفترة محدودة لتعيين كلمة مرور جديدة."
                    :perks="[['fa-solid fa-envelope-open-text', 'تحقق من صندوق الوارد والرسائل غير المرغوب فيها'], ['fa-solid fa-clock', 'الرابط صالح لفترة محدودة ولمرة واحدة'], ['fa-solid fa-key', 'اختر كلمة مرور قوية لم تستخدمها من قبل']]">
                    تذكرت كلمة المرور؟ <a href="{{ route('login') }}">سجّل الدخول</a>
                </x-kfs.auth-aside>

                <section class="kfs-auth__main" aria-labelledby="forgot-title">
                    <header class="kfs-auth__head">
                        <span class="kfs-iframe kfs-iframe--lg" aria-hidden="true"><i class="fa-solid fa-key"></i></span>
                        <div>
                            <h2 id="forgot-title" class="h3 mb-0">هل نسيت كلمة المرور؟</h2>
                            <p>لا مشكلة. أدخل بريدك الإلكتروني وسنرسل لك رابطًا لإعادة التعيين.</p>
                        </div>
                    </header>

                    @if (session('status'))
                        <div class="kfs-note kfs-note--success" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><div>{{ session('status') }}</div></div>
                        <script>Swal.fire({ icon:'success', title:'تم', text:{{ json_encode(session('status')) }}, confirmButtonText:'حسناً' });</script>
                    @endif
                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf
                        <div class="kfs-ffield kfs-ffield--icon">
                            <i class="fa-solid fa-envelope kfs-ffield__icon" aria-hidden="true"></i>
                            <input id="email" type="email" class="@error('email') is-invalid @enderror"
                                name="email" value="{{ old('email') }}" placeholder=" " dir="ltr" autocomplete="email" required autofocus
                                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                            <label for="email">البريد الإلكتروني</label>
                            @error('email')
                                <span class="invalid-feedback" id="email-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn-kfs kfs-btn-lg kfs-btn-block">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> إرسال رابط إعادة التعيين
                        </button>
                        <p class="kfs-auth__alt">
                            <a href="{{ route('login') }}"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> العودة لتسجيل الدخول</a>
                        </p>
                    </form>
                </section>
            </div>
        </div>
    </main>
@endsection
