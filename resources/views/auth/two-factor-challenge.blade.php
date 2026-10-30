@extends('layouts.app')
@section('title', 'التحقق الثنائي')
<x-kfs.portal-assets />
@section('content')
    <main class="main-content kfs-portal" dir="rtl">
        <x-kfs.page-hero size="compact" title="التحقق الثنائي" kicker="أمان الحساب" subtitle="خطوة أخيرة لإكمال تسجيل الدخول بأمان." icon="fa-solid fa-shield-halved" :image="asset('images/bg/register.jpg')" :crumbs="['تسجيل الدخول' => route('login')]" />

        <div class="container">
            <div class="kfs-auth__shell kfs-auth__shell--narrow" data-reveal>
                <x-kfs.auth-aside title="خطوة أخيرة لحماية حسابك" text="التحقق الثنائي يضمن أنك وحدك من يستطيع الدخول إلى حسابك حتى لو عرف غيرك كلمة المرور."
                    :perks="[['fa-solid fa-mobile-screen', 'افتح تطبيق Google Authenticator'], ['fa-solid fa-hashtag', 'أدخل الرمز المكوَّن من 6 أرقام'], ['fa-solid fa-life-ring', 'فقدت هاتفك؟ استخدم رمز استرداد']]" />

                <section class="kfs-auth__main" aria-labelledby="tfa-title">
                    <header class="kfs-auth__head">
                        <span class="kfs-iframe kfs-iframe--lg" aria-hidden="true"><i class="fas fa-shield-halved"></i></span>
                        <div>
                            <h2 id="tfa-title" class="h3 mb-0">التحقق الثنائي</h2>
                            <p>افتح تطبيق Google Authenticator وأدخل الرمز المكوَّن من 6 أرقام لإكمال تسجيل الدخول.</p>
                        </div>
                    </header>

                    @if (session('warning'))
                        <div class="kfs-note kfs-note--warning" role="status"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><div>{{ session('warning') }}</div></div>
                    @endif
                    @if ($errors->any())
                        <div class="kfs-note kfs-note--danger" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><div>{{ $errors->first() }}</div></div>
                    @endif

                    <form method="POST" action="{{ $action }}" id="tfa-code-form">
                        @csrf
                        <div class="kfs-ffield kfs-otp" id="tfa-code-group">
                            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9 ]*"
                                autocomplete="one-time-code" maxlength="7" class="tfa-code-input" placeholder="000000" autofocus>
                            <label for="code">رمز التحقق</label>
                        </div>
                        <div class="kfs-ffield kfs-ffield--icon d-none" id="tfa-recovery-group">
                            <i class="fa-solid fa-life-ring kfs-ffield__icon" aria-hidden="true"></i>
                            <input id="recovery_code" name="recovery_code" type="text" autocomplete="off"
                                dir="ltr" placeholder=" ">
                            <label for="recovery_code">رمز الاسترداد (XXXXX-XXXXX)</label>
                        </div>
                        <button type="submit" class="btn-kfs kfs-btn-lg kfs-btn-block">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i> تحقق ومتابعة
                        </button>
                        <p class="text-center mt-3 mb-0">
                            <button type="button" class="kfs-linkbtn tfa-toggle-link" id="tfa-toggle">فقدت هاتفك؟ استخدم رمز استرداد</button>
                        </p>
                    </form>

                    <div class="kfs-auth__divider" aria-hidden="true">أو</div>
                    <div class="text-center mt-2">
                        @if ($cancelUrl)
                            <a href="{{ $cancelUrl }}" class="kfs-linkbtn d-inline-flex align-items-center gap-2"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> العودة لتسجيل الدخول</a>
                        @else
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="kfs-linkbtn tfa-toggle-link"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> تسجيل الخروج</button>
                            </form>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </main>
    <script>
        (function () {
            var toggle = document.getElementById('tfa-toggle');
            var codeGroup = document.getElementById('tfa-code-group');
            var recGroup = document.getElementById('tfa-recovery-group');
            var usingRecovery = false;
            toggle.addEventListener('click', function () {
                usingRecovery = !usingRecovery;
                codeGroup.classList.toggle('d-none', usingRecovery);
                recGroup.classList.toggle('d-none', !usingRecovery);
                document.getElementById(usingRecovery ? 'code' : 'recovery_code').value = '';
                document.getElementById(usingRecovery ? 'recovery_code' : 'code').focus();
                toggle.textContent = usingRecovery ? 'استخدام رمز التطبيق بدلًا من ذلك' : 'فقدت هاتفك؟ استخدم رمز استرداد';
            });
        })();
    </script>
@endsection
