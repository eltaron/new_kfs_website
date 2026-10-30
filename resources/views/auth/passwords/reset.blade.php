@extends('layouts.app')
@section('title', 'تعيين كلمة مرور جديدة')
<x-kfs.portal-assets js />
@section('content')
    <main class="main-content kfs-portal">
        <x-kfs.page-hero size="compact" title="تعيين كلمة مرور جديدة" kicker="أمان الحساب" subtitle="اختر كلمة مرور قوية تحمي حسابك وبياناتك على البوابة." icon="fa-solid fa-unlock-keyhole" :image="asset('images/bg/register.jpg')" :crumbs="['تسجيل الدخول' => route('login')]" />

        <div class="container">
            <div class="kfs-auth__shell kfs-auth__shell--narrow" data-reveal>
                <x-kfs.auth-aside title="كلمة مرور جديدة وآمنة" text="اختر كلمة مرور قوية تحمي حسابك وبياناتك على البوابة."
                    :perks="[['fa-solid fa-text-height', '8 أحرف على الأقل'], ['fa-solid fa-font', 'حروف كبيرة وصغيرة'], ['fa-solid fa-hashtag', 'أرقام ورموز خاصة'], ['fa-solid fa-ban', 'لا تستخدم كلمة مرور سابقة']]" />

                <section class="kfs-auth__main" aria-labelledby="reset-title">
                    <header class="kfs-auth__head">
                        <span class="kfs-iframe kfs-iframe--lg" aria-hidden="true"><i class="fa-solid fa-unlock-keyhole"></i></span>
                        <div>
                            <h2 id="reset-title" class="h3 mb-0">تعيين كلمة مرور جديدة</h2>
                            <p>أدخل بريدك الإلكتروني ثم كلمة المرور الجديدة مرتين للتأكيد.</p>
                        </div>
                    </header>

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">
                        <div class="kfs-ffield kfs-ffield--icon">
                            <i class="fa-solid fa-envelope kfs-ffield__icon" aria-hidden="true"></i>
                            <input id="email" type="email" class="@error('email') is-invalid @enderror"
                                name="email" value="{{ $request->email ?? old('email') }}" placeholder=" " dir="ltr" autocomplete="email" required autofocus>
                            <label for="email">البريد الإلكتروني</label>
                            @error('email')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="kfs-ffield kfs-ffield--icon kfs-ffield--pw">
                            <i class="fa-solid fa-lock kfs-ffield__icon" aria-hidden="true"></i>
                            <input id="password" type="password"
                                class="@error('password') is-invalid @enderror" name="password" placeholder=" " autocomplete="new-password" required>
                            <label for="password">كلمة المرور الجديدة</label>
                            <button type="button" class="kfs-pw-toggle" data-pw-toggle="password" aria-label="إظهار كلمة المرور" aria-pressed="false" hidden>
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </button>
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="kfs-ffield kfs-ffield--icon kfs-ffield--pw">
                            <i class="fa-solid fa-lock kfs-ffield__icon" aria-hidden="true"></i>
                            <input id="password-confirm" type="password" name="password_confirmation" placeholder=" " autocomplete="new-password"
                                required>
                            <label for="password-confirm">تأكيد كلمة المرور</label>
                            <button type="button" class="kfs-pw-toggle" data-pw-toggle="password-confirm" aria-label="إظهار كلمة المرور" aria-pressed="false" hidden>
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <button type="submit" class="btn-kfs kfs-btn-lg kfs-btn-block">
                            <i class="fa-solid fa-check" aria-hidden="true"></i> إعادة تعيين كلمة المرور
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </main>
@endsection
