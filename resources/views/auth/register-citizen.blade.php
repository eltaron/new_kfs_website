@extends('layouts.app')

@section('title', 'تسجيل مواطن جديد')
<x-kfs.portal-assets js />
@section('content')
    <main class="main-content kfs-portal">
        {{-- Page Header --}}
        <x-kfs.page-hero title="إنشاء حساب مستخدم" kicker="البوابة الإلكترونية" subtitle="انضم إلى بوابتنا الرقمية للاستفادة من الخدمات الإلكترونية والتفاعل مع الجهات الحكومية." icon="fa-solid fa-user-plus" :image="asset('images/bg/register.jpg')" />

        <div class="container">
            <div class="kfs-auth__shell kfs-auth__shell--wide" data-reveal>
                <x-kfs.auth-aside title="حساب واحد لكل خدماتك" text="جهّز بطاقة الرقم القومي قبل البدء؛ سنراجع بياناتك ونفعّل حسابك في أقرب وقت."
                    :perks="[['fa-solid fa-user', 'البيانات الشخصية كما في البطاقة'], ['fa-solid fa-key', 'كلمة مرور قوية لحماية حسابك'], ['fa-solid fa-id-card', 'صورة واضحة من وجه البطاقة'], ['fa-solid fa-user-check', 'مراجعة الحساب وتفعيله']]">
                    لديك حساب بالفعل؟ <a href="{{ route('login') }}">سجّل الدخول</a>
                </x-kfs.auth-aside>

                <section class="kfs-auth__main" aria-labelledby="register-title">
                    <header class="kfs-auth__head">
                        <span class="kfs-iframe kfs-iframe--lg" aria-hidden="true"><i class="fa-solid fa-user-plus"></i></span>
                        <div>
                            <h2 id="register-title" class="h3 mb-0">تسجيل حساب جديد</h2>
                            <p>يرجى إدخال بياناتك بشكل صحيح كما هي مدونة في بطاقة الرقم القومي.</p>
                        </div>
                    </header>

                    <ol class="kfs-steps-nav" aria-label="مراحل التسجيل">
                        <li>البيانات الشخصية</li>
                        <li>بيانات الحساب</li>
                        <li>إثبات الهوية</li>
                    </ol>

                    @if ($errors->any())
                        <div class="kfs-note kfs-note--danger" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                            <div><strong>خطأ في البيانات!</strong> يرجى مراجعة الحقول وإصلاح الأخطاء.</div>
                        </div>
                    @endif

                    <form action="{{ route('register.citizen.store') }}" method="POST" enctype="multipart/form-data"
                        class="needs-validation" novalidate>
                        @csrf

                        {{-- Step 1: Personal Information --}}
                        <fieldset class="kfs-fstep form-step">
                            <legend class="kfs-fstep__title"><span class="kfs-step-num" aria-hidden="true">1</span> البيانات الشخصية</legend>
                            <div class="kfs-fgrid">
                                <div class="kfs-ffield kfs-ffield--icon is-full">
                                    <i class="fa-solid fa-user kfs-ffield__icon" aria-hidden="true"></i>
                                    <input type="text" id="name" name="name" placeholder=" " autocomplete="name"
                                        class="@error('name') is-invalid @enderror"
                                        value="{{ old('name') }}" required>
                                    <label for="name">الاسم الكامل (رباعي) <span class="kfs-req" aria-hidden="true">*</span></label>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="kfs-ffield kfs-ffield--icon">
                                    <i class="fa-solid fa-envelope kfs-ffield__icon" aria-hidden="true"></i>
                                    <input type="email" id="email" name="email" placeholder=" " dir="ltr" autocomplete="email"
                                        class="@error('email') is-invalid @enderror"
                                        value="{{ old('email') }}">
                                    <label for="email">البريد الإلكتروني</label>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="kfs-ffield kfs-ffield--icon">
                                    <i class="fa-solid fa-phone kfs-ffield__icon" aria-hidden="true"></i>
                                    <input type="tel" id="phone" name="phone" placeholder=" " dir="ltr" inputmode="tel" autocomplete="tel"
                                        class="@error('phone') is-invalid @enderror"
                                        value="{{ old('phone') }}" required>
                                    <label for="phone">رقم الهاتف <span class="kfs-req" aria-hidden="true">*</span></label>
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="kfs-ffield kfs-ffield--icon is-full">
                                    <i class="fa-solid fa-location-dot kfs-ffield__icon" aria-hidden="true"></i>
                                    <input type="text" id="address" name="address" placeholder=" " autocomplete="street-address"
                                        class="@error('address') is-invalid @enderror"
                                        value="{{ old('address') }}">
                                    <label for="address">العنوان (كما هو مسجل بالبطاقة)</label>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="kfs-ffield kfs-ffield--icon is-full">
                                    <i class="fa-solid fa-briefcase kfs-ffield__icon" aria-hidden="true"></i>
                                    <input type="text" id="job_title" name="job_title" placeholder=" " autocomplete="organization-title"
                                        class="@error('job_title') is-invalid @enderror"
                                        value="{{ old('job_title') }}">
                                    <label for="job_title">الوظيفة (اختياري)</label>
                                </div>
                            </div>
                        </fieldset>

                        {{-- Step 2: Account Information --}}
                        <fieldset class="kfs-fstep form-step">
                            <legend class="kfs-fstep__title"><span class="kfs-step-num" aria-hidden="true">2</span> بيانات الحساب</legend>
                            <div class="kfs-fgrid">
                                <div class="kfs-ffield kfs-ffield--icon is-full">
                                    <i class="fa-solid fa-id-card kfs-ffield__icon" aria-hidden="true"></i>
                                    <input type="text" id="national_id" name="national_id" placeholder=" " dir="ltr" inputmode="numeric"
                                        class="@error('national_id') is-invalid @enderror" pattern="\d{14}"
                                        title="يجب إدخال 14 رقمًا" value="{{ old('national_id') }}" required>
                                    <label for="national_id">الرقم القومي (14 رقم) <span class="kfs-req" aria-hidden="true">*</span></label>
                                    @error('national_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="kfs-ffield kfs-ffield--icon kfs-ffield--pw">
                                    <i class="fa-solid fa-lock kfs-ffield__icon" aria-hidden="true"></i>
                                    <input type="password" id="password" name="password" placeholder=" " autocomplete="new-password"
                                        class="@error('password') is-invalid @enderror" required>
                                    <label for="password">كلمة المرور <span class="kfs-req" aria-hidden="true">*</span></label>
                                    <button type="button" class="kfs-pw-toggle" data-pw-toggle="password" aria-label="إظهار كلمة المرور" aria-pressed="false" hidden>
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>

                                    {{-- Display all password-related errors --}}
                                    @error('password')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @else
                                        <small class="kfs-ffield__hint">يجب أن تحتوي على 8 أحرف على الأقل، مع حروف كبيرة
                                            وصغيرة وأرقام ورموز.</small>
                                    @enderror
                                </div>

                                <div class="kfs-ffield kfs-ffield--icon kfs-ffield--pw">
                                    <i class="fa-solid fa-lock kfs-ffield__icon" aria-hidden="true"></i>
                                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder=" " autocomplete="new-password"
                                        required>
                                    <label for="password_confirmation">تأكيد كلمة المرور <span class="kfs-req" aria-hidden="true">*</span></label>
                                    <button type="button" class="kfs-pw-toggle" data-pw-toggle="password_confirmation" aria-label="إظهار كلمة المرور" aria-pressed="false" hidden>
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </fieldset>

                        {{-- Step 3: Document Upload --}}
                        <fieldset class="kfs-fstep form-step">
                            <legend class="kfs-fstep__title"><span class="kfs-step-num" aria-hidden="true">3</span> إثبات الهوية</legend>
                            <div class="kfs-ffield">
                                <input type="file" id="national_id_image" name="national_id_image"
                                    class="@error('national_id_image') is-invalid @enderror" aria-describedby="national_id_image-hint" required>
                                <label for="national_id_image">صورة واضحة من بطاقة الرقم القومي (وجه أمامي) <span class="kfs-req" aria-hidden="true">*</span></label>
                                <small class="kfs-ffield__hint" id="national_id_image-hint"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> سيتم استخدام هذه الصورة لمراجعة وتفعيل حسابك.
                                    الأنواع المسموح بها: JPG, PNG.</small>
                                @error('national_id_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </fieldset>

                        <button type="submit" class="btn-kfs kfs-btn-lg kfs-btn-block">
                            <i class="fa-solid fa-user-check" aria-hidden="true"></i> إنشاء الحساب
                        </button>
                        <p class="kfs-auth__secure"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> بياناتك محفوظة ومشفّرة ولا تُستخدم إلا لتقديم الخدمات الحكومية.</p>
                    </form>
                </section>
            </div>
        </div>
    </main>
@endsection
