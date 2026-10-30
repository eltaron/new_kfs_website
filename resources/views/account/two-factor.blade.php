@extends('layouts.app')
@section('title', 'المصادقة الثنائية')
<x-kfs.portal-assets js />
@section('content')
    <main class="main-content kfs-portal" dir="rtl">
        <x-kfs.page-hero title="المصادقة الثنائية" kicker="أمان الحساب" subtitle="أضف طبقة حماية إضافية لحسابك باستخدام تطبيق Google Authenticator." icon="fa-solid fa-shield-halved" />

        <div class="container">
            <div class="kfs-acc">
                <div>
                    <section class="kfs-pcard kfs-pcard--cyan" data-reveal aria-labelledby="tfa-main-title">
                        <div class="kfs-pcard__head">
                            <span class="kfs-iframe kfs-iframe--lg" aria-hidden="true"><i class="fas fa-shield-halved"></i></span>
                            <div>
                                <h2 class="kfs-pcard__title" id="tfa-main-title">المصادقة الثنائية (Google Authenticator)</h2>
                                <p class="kfs-pcard__sub">بعد كلمة المرور سيُطلب منك رمز متغير من تطبيق Google Authenticator على هاتفك.</p>
                            </div>
                            <span class="kfs-pill {{ $enabled ? 'on' : 'off' }}">
                                <i class="fas {{ $enabled ? 'fa-circle-check' : 'fa-circle-xmark' }}" aria-hidden="true"></i>
                                {{ $enabled ? 'مفعّلة' : 'غير مفعّلة' }}
                            </span>
                        </div>

                        @if (session('status'))
                            <div class="kfs-note kfs-note--success" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><div>{{ session('status') }}</div></div>
                        @endif
                        @if (session('warning'))
                            <div class="kfs-note kfs-note--warning" role="status"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><div>{{ session('warning') }}</div></div>
                        @endif
                        @if ($required && ! $enabled)
                            <div class="kfs-note kfs-note--warning" role="status"><i class="fa-solid fa-user-shield" aria-hidden="true"></i><div>المصادقة الثنائية إلزامية لحسابات الموظفين. يرجى تفعيلها للمتابعة.</div></div>
                        @endif
                        @if ($errors->any())
                            <div class="kfs-note kfs-note--danger" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><div>{{ $errors->first() }}</div></div>
                        @endif

                        @if (! $enabled && ! $pending)
                            <form method="POST" action="{{ route('two-factor.enable') }}">
                                @csrf
                                <div class="kfs-ffield kfs-ffield--icon kfs-ffield--pw">
                                    <i class="fa-solid fa-lock kfs-ffield__icon" aria-hidden="true"></i>
                                    <input id="password" type="password" name="password" placeholder=" " required autocomplete="current-password">
                                    <label for="password">أكّد كلمة المرور للبدء</label>
                                    <button type="button" class="kfs-pw-toggle" data-pw-toggle="password" aria-label="إظهار كلمة المرور" aria-pressed="false" hidden>
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <button type="submit" class="btn-kfs kfs-btn-lg"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> تفعيل المصادقة الثنائية</button>
                            </form>
                        @elseif ($pending)
                            <div class="kfs-acc__step">
                                <span class="kfs-step-num" aria-hidden="true">1</span>
                                <div>
                                    <h2>امسح رمز QR</h2>
                                    <p class="mb-1">افتح تطبيق Google Authenticator (أو أي تطبيق TOTP) واختر «إضافة حساب» ثم امسح الرمز التالي:</p>
                                    <div class="kfs-qr" role="img" aria-label="رمز QR للمصادقة الثنائية">{!! $qrSvg !!}</div>
                                    <p class="small mb-0">أو أدخل المفتاح يدويًا: <span class="kfs-secret">{{ trim(chunk_split($secret, 4, ' ')) }}</span></p>
                                </div>
                            </div>

                            <div class="kfs-acc__step">
                                <span class="kfs-step-num" aria-hidden="true">2</span>
                                <div>
                                    <h2>أدخل الرمز للتأكيد</h2>
                                    <form method="POST" action="{{ route('two-factor.confirm') }}" class="mb-3">
                                        @csrf
                                        <div class="kfs-ffield kfs-otp">
                                            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                                                maxlength="7" class="tfa-code-input" placeholder="000000" required autofocus>
                                            <label for="code">الرمز المكوَّن من 6 أرقام</label>
                                        </div>
                                        <button type="submit" class="btn-kfs kfs-btn-lg"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> تأكيد التفعيل</button>
                                    </form>
                                    <form method="POST" action="{{ route('two-factor.cancel') }}">
                                        @csrf
                                        <button type="submit" class="btn-kfs-outline"><i class="fa-solid fa-xmark" aria-hidden="true"></i> إلغاء</button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <dl class="kfs-kv">
                                <div>
                                    <dt>تم التفعيل بتاريخ</dt>
                                    <dd dir="ltr" class="text-end">{{ optional($user->two_factor_confirmed_at)->format('Y-m-d H:i') }}</dd>
                                </div>
                                <div>
                                    <dt>رموز الاسترداد المتبقية</dt>
                                    <dd class="kfs-num">{{ $remainingCodes }}</dd>
                                </div>
                            </dl>
                        @endif
                    </section>

                    @if (! empty($recoveryCodes))
                        <section class="kfs-pcard" data-reveal aria-labelledby="tfa-codes-title">
                            <div class="kfs-pcard__head">
                                <span class="kfs-iframe" aria-hidden="true"><i class="fa-solid fa-life-ring"></i></span>
                                <div>
                                    <h2 class="kfs-pcard__title" id="tfa-codes-title">رموز الاسترداد</h2>
                                    <p class="kfs-pcard__sub">احفظ هذه الرموز في مكان آمن. يمكن استخدام كل رمز مرة واحدة فقط لتسجيل الدخول إذا فقدت هاتفك. لن تظهر مرة أخرى.</p>
                                </div>
                                <button type="button" class="btn-kfs-outline kfs-copy-btn" data-copy-target="tfa-codes-list" hidden>
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i> <span>نسخ الرموز</span>
                                </button>
                            </div>
                            <ul class="kfs-codes" id="tfa-codes-list">
                                @foreach ($recoveryCodes as $code)
                                    <li>{{ $code }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($enabled)
                        <section class="kfs-pcard" data-reveal aria-labelledby="tfa-regen-title">
                            <div class="kfs-pcard__head">
                                <span class="kfs-iframe" aria-hidden="true"><i class="fa-solid fa-rotate"></i></span>
                                <div>
                                    <h2 class="kfs-pcard__title" id="tfa-regen-title">إنشاء رموز استرداد جديدة</h2>
                                    <p class="kfs-pcard__sub">ستتوقف الرموز القديمة عن العمل فور إنشاء رموز جديدة.</p>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('two-factor.recovery-codes') }}">
                                @csrf
                                <div class="kfs-ffield kfs-ffield--icon kfs-ffield--pw">
                                    <i class="fa-solid fa-lock kfs-ffield__icon" aria-hidden="true"></i>
                                    <input id="rc_password" type="password" name="password" placeholder=" " required autocomplete="current-password">
                                    <label for="rc_password">كلمة المرور</label>
                                    <button type="button" class="kfs-pw-toggle" data-pw-toggle="rc_password" aria-label="إظهار كلمة المرور" aria-pressed="false" hidden>
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <button type="submit" class="btn-kfs-outline"><i class="fa-solid fa-rotate" aria-hidden="true"></i> إنشاء رموز جديدة</button>
                            </form>
                        </section>

                        @unless ($required)
                            <section class="kfs-pcard kfs-pcard--danger" data-reveal aria-labelledby="tfa-disable-title">
                                <div class="kfs-pcard__head">
                                    <span class="kfs-iframe kfs-iframe--bad" aria-hidden="true"><i class="fa-solid fa-shield"></i></span>
                                    <div>
                                        <h2 class="kfs-pcard__title" id="tfa-disable-title">تعطيل المصادقة الثنائية</h2>
                                        <p class="kfs-pcard__sub">لا ننصح بذلك؛ سيصبح حسابك محميًا بكلمة المرور فقط.</p>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('two-factor.disable') }}">
                                    @csrf
                                    @method('DELETE')
                                    <div class="kfs-fgrid">
                                        <div class="kfs-ffield kfs-ffield--icon kfs-ffield--pw">
                                            <i class="fa-solid fa-lock kfs-ffield__icon" aria-hidden="true"></i>
                                            <input id="d_password" type="password" name="password" placeholder=" " required autocomplete="current-password">
                                            <label for="d_password">كلمة المرور</label>
                                            <button type="button" class="kfs-pw-toggle" data-pw-toggle="d_password" aria-label="إظهار كلمة المرور" aria-pressed="false" hidden>
                                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                        <div class="kfs-ffield kfs-ffield--icon">
                                            <i class="fa-solid fa-hashtag kfs-ffield__icon" aria-hidden="true"></i>
                                            <input id="d_code" type="text" name="code" dir="ltr" placeholder=" " required autocomplete="one-time-code">
                                            <label for="d_code">رمز التطبيق أو رمز استرداد</label>
                                        </div>
                                    </div>
                                    <button type="submit" class="kfs-btn-danger"><i class="fa-solid fa-power-off" aria-hidden="true"></i> تعطيل</button>
                                </form>
                            </section>
                        @endunless
                    @endif
                </div>

                <aside class="kfs-acc__aside" aria-label="إرشادات">
                    <div class="kfs-pcard" data-reveal>
                        <h2 class="kfs-pcard__title mb-3"><i class="fa-solid fa-lightbulb text-primary" aria-hidden="true"></i> كيف تعمل؟</h2>
                        <ul class="kfs-acc__tips">
                            <li><i class="fa-solid fa-mobile-screen" aria-hidden="true"></i><span>ثبّت تطبيق Google Authenticator أو أي تطبيق TOTP على هاتفك.</span></li>
                            <li><i class="fa-solid fa-qrcode" aria-hidden="true"></i><span>امسح رمز QR مرة واحدة لربط حسابك بالتطبيق.</span></li>
                            <li><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>يتغير الرمز كل 30 ثانية ويُطلب عند كل تسجيل دخول.</span></li>
                            <li><i class="fa-solid fa-life-ring" aria-hidden="true"></i><span>احتفظ برموز الاسترداد بعيدًا عن هاتفك.</span></li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </main>
@endsection
