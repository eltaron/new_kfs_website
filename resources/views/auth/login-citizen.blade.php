@extends('layouts.app')
@section('title', 'تسجيل الدخول')
<x-kfs.portal-assets js />
@section('content')
    <main class="main-content kfs-portal">
        <x-kfs.page-hero size="compact" title="تسجيل الدخول" kicker="البوابة الإلكترونية" subtitle="ادخل إلى حسابك لمتابعة طلباتك وخدماتك الإلكترونية." icon="fa-solid fa-right-to-bracket" :image="asset('images/bg/register.jpg')" :crumbs="[]" />

        <div class="container">
            <div class="kfs-auth__shell" data-reveal>
                <x-kfs.auth-aside>
                    ليس لديك حساب بعد؟ <a href="{{ route('register.citizen') }}">أنشئ حسابك في دقائق</a>
                </x-kfs.auth-aside>

                <section class="kfs-auth__main" aria-labelledby="login-title">
                    <header class="kfs-auth__head">
                        <span class="kfs-iframe kfs-iframe--lg" aria-hidden="true"><i class="fa-solid fa-right-to-bracket"></i></span>
                        <div>
                            <h2 id="login-title" class="h3 mb-0">تسجيل الدخول إلى البوابة</h2>
                            <p>استخدم الرقم القومي وكلمة المرور للوصول إلى حسابك.</p>
                        </div>
                    </header>

                    @if (session('success'))
                        <div class="kfs-note kfs-note--success" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><div>{{ session('success') }}</div></div>
                    @endif
                    @if (session('warning'))
                        <div class="kfs-note kfs-note--warning" role="status"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><div>{{ session('warning') }}</div></div>
                    @endif
                    @if ($errors->any())
                        <div class="kfs-note kfs-note--danger" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                            <div>{{ $errors->first() }}</div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        {{-- National ID Field --}}
                        <div class="kfs-ffield kfs-ffield--icon">
                            <i class="fa-solid fa-id-card kfs-ffield__icon" aria-hidden="true"></i>
                            <input id="national_id" type="text" name="national_id" inputmode="numeric" autocomplete="username"
                                value="{{ old('national_id') }}" placeholder=" " dir="ltr" required autofocus>
                            <label for="national_id">الرقم القومي</label>
                        </div>
                        {{-- Password Field --}}
                        <div class="kfs-ffield kfs-ffield--icon kfs-ffield--pw">
                            <i class="fa-solid fa-lock kfs-ffield__icon" aria-hidden="true"></i>
                            <input id="password" type="password" name="password" placeholder=" " autocomplete="current-password" required>
                            <label for="password">كلمة المرور</label>
                            <button type="button" class="kfs-pw-toggle" data-pw-toggle="password" aria-label="إظهار كلمة المرور" aria-pressed="false" hidden>
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>

                        <div class="kfs-auth__row">
                            {{-- Remember Me --}}
                            <label class="kfs-check" for="remember">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                    {{ old('remember') ? 'checked' : '' }}>
                                تذكرني
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
                            @endif
                        </div>

                        {{-- Submit Button --}}
                        <button type="submit" class="btn-kfs kfs-btn-lg kfs-btn-block">
                            <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> تسجيل الدخول
                        </button>
                        <p class="kfs-auth__secure"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> اتصال مشفّر وآمن — لا نشارك بياناتك مع أي جهة.</p>

                        <div class="kfs-auth__divider" aria-hidden="true">أو</div>
                        <p class="kfs-auth__alt">ليس لديك حساب؟ <a href="{{ route('register.citizen') }}">أنشئ حسابًا جديدًا</a></p>
                    </form>
                </section>
            </div>
        </div>
    </main>
@endsection
