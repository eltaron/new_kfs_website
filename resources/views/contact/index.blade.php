@extends('layouts.app')
@section('title', 'الدعم الفني')
@section('description', 'تواصل مع فريق الدعم الفني لموقع محافظة كفر الشيخ لحل أي مشكلة تقنية في الموقع أو الخدمات الإلكترونية.')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/kfs-help.css') }}?v={{ @filemtime(public_path('css/kfs-help.css')) }}">
@endpush

@section('content')
    @php
        $topics = [
            ['fa-solid fa-right-to-bracket', 'مشكلة في تسجيل الدخول'],
            ['fa-solid fa-user-plus', 'مشكلة في إنشاء حساب'],
            ['fa-solid fa-laptop-file', 'خطأ في خدمة إلكترونية'],
            ['fa-solid fa-credit-card', 'مشكلة في الدفع الإلكتروني'],
            ['fa-solid fa-bug', 'صفحة لا تعمل أو بها خطأ'],
            ['fa-solid fa-lightbulb', 'اقتراح لتطوير الموقع'],
        ];
        $user = auth()->user();
        $routes = [
            ['fa-solid fa-file-pen', 'شكوى ضد جهة حكومية؟', 'قدّمها عبر منظومة الشكاوى', Route::has('requests.index') ? route('requests.index') : null],
            ['fa-solid fa-triangle-exclamation', 'حالة طارئة؟', 'سجّل بلاغاً عاجلاً', Route::has('emergency.create') ? route('emergency.create') : null],
            ['fa-solid fa-address-book', 'تبحث عن رقم هاتف؟', 'دليل الهاتف والخدمات', Route::has('directory.index') ? route('directory.index') : null],
        ];
    @endphp

    <main class="kh kh--support">
        <x-kfs.page-hero size="compact" title="الدعم الفني" kicker="تواصل معنا" icon="fa-solid fa-headset"
            subtitle="واجهتك مشكلة تقنية في الموقع أو الخدمات الإلكترونية؟ اكتب لنا وسنرد عليك في أقرب وقت." />

        <div class="container kh__wrap">
            @if (session('success'))
                <section class="kh-done" role="status" aria-live="polite">
                    <span class="kh-done__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                    <div class="kh-done__body">
                        <h2>تم استلام رسالتك</h2>
                        <p>{{ session('success') }}</p>
                    </div>
                    <a href="{{ route('home') }}" class="kh-btn kh-btn--ghost">العودة للرئيسية</a>
                </section>
            @endif

            @if ($errors->any())
                <section class="kh-errors" role="alert">
                    <h2><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> يرجى مراجعة البيانات التالية</h2>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <div class="kh-form-layout">
                <form action="{{ route('contact.store') }}" method="POST" class="kh-form" id="khForm">
                    @csrf

                    {{-- 1. Topic --}}
                    <fieldset class="kh-card">
                        <legend class="kh-card__title"><span class="kh-card__num">1</span> ما المشكلة التي تواجهك؟</legend>
                        <div class="kh-topics" role="group" aria-label="اختر موضوعاً شائعاً">
                            @foreach ($topics as [$icon, $label])
                                <button type="button" class="kh-topic" data-topic="{{ $label }}" aria-pressed="false">
                                    <i class="{{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
                                </button>
                            @endforeach
                        </div>
                        <div class="kh-field kh-field--topic">
                            <label for="subject" class="kh-label">عنوان الطلب <span class="kh-req">*</span></label>
                            <input type="text" id="subject" name="subject" class="kh-input" maxlength="255" required
                                value="{{ old('subject') }}" placeholder="اختر من الأعلى أو اكتب عنواناً مختصراً">
                            @error('subject')<p class="kh-field-error">{{ $message }}</p>@enderror
                        </div>
                    </fieldset>

                    {{-- 2. Details --}}
                    <fieldset class="kh-card">
                        <legend class="kh-card__title"><span class="kh-card__num">2</span> اشرح المشكلة</legend>
                        <div class="kh-field">
                            <label for="message" class="kh-label">التفاصيل <span class="kh-req">*</span></label>
                            <textarea id="message" name="message" class="kh-input" rows="6" maxlength="5000" required
                                placeholder="ماذا كنت تحاول أن تفعل؟ ما الرسالة التي ظهرت لك؟ ومن أي جهاز ومتصفح؟">{{ old('message') }}</textarea>
                            <div class="kh-field-foot">
                                @error('message')<p class="kh-field-error">{{ $message }}</p>@else<span>كلما كانت التفاصيل أوضح، كان الحل أسرع.</span>@enderror
                                <small class="kh-count" id="khCount" aria-live="polite">0 / 5000</small>
                            </div>
                        </div>
                    </fieldset>

                    {{-- 3. Contact --}}
                    <fieldset class="kh-card">
                        <legend class="kh-card__title"><span class="kh-card__num">3</span> بيانات التواصل معك</legend>
                        <div class="kh-grid">
                            <div class="kh-field kh-field--full">
                                <label for="name" class="kh-label">الاسم الكامل <span class="kh-req">*</span></label>
                                <input type="text" id="name" name="name" class="kh-input" maxlength="255" required autocomplete="name"
                                    value="{{ old('name', $user?->name) }}">
                                @error('name')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="kh-field">
                                <label for="email" class="kh-label">البريد الإلكتروني <span class="kh-opt">(اختياري)</span></label>
                                <input type="email" id="email" name="email" class="kh-input" dir="ltr" maxlength="255" autocomplete="email"
                                    value="{{ old('email', $user?->email) }}" placeholder="name@example.com">
                                @error('email')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="kh-field">
                                <label for="phone" class="kh-label">رقم الهاتف <span class="kh-opt">(اختياري)</span></label>
                                <input type="tel" id="phone" name="phone" class="kh-input" dir="ltr" inputmode="tel" maxlength="20" autocomplete="tel"
                                    value="{{ old('phone', $user?->phone) }}" placeholder="01XXXXXXXXX">
                                @error('phone')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <p class="kh-hint"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> أضف البريد أو الهاتف حتى نتمكن من الرد عليك.</p>
                    </fieldset>

                    <div class="kh-submit">
                        <button type="submit" class="kh-btn kh-btn--accent kh-btn--lg" id="khSubmit">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>إرسال الطلب</span>
                        </button>
                        <p><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> بياناتك مؤمّنة ولا تُستخدم إلا للرد على طلبك.</p>
                    </div>
                </form>

                <aside class="kh-side" aria-label="معلومات الدعم الفني">
                    <div class="kh-side__card kh-side__card--brand">
                        <span class="kh-side__badge" aria-hidden="true"><i class="fa-solid fa-life-ring"></i></span>
                        <h2>فريق الدعم الفني</h2>
                        <dl class="kh-facts">
                            <div><dt><i class="fa-solid fa-clock" aria-hidden="true"></i> أوقات العمل</dt><dd>الأحد – الخميس · 9 ص – 3 م</dd></div>
                            <div><dt><i class="fa-solid fa-reply-all" aria-hidden="true"></i> زمن الرد</dt><dd>خلال 24 ساعة عمل</dd></div>
                            <div><dt><i class="fa-solid fa-ranking-star" aria-hidden="true"></i> الأولوية</dt><dd>حسب درجة الاستعجال</dd></div>
                        </dl>
                    </div>
                    <div class="kh-side__card">
                        <h2><i class="fa-solid fa-signs-post" aria-hidden="true"></i> هل أنت في المكان الصحيح؟</h2>
                        <div class="kh-routes">
                            @foreach ($routes as [$icon, $title, $sub, $url])
                                @if ($url)
                                    <a href="{{ $url }}" class="kh-route">
                                        <span class="kh-route__icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                                        <span class="kh-route__body"><strong>{{ $title }}</strong><small>{{ $sub }}</small></span>
                                        <i class="fa-solid fa-arrow-left kh-route__go" aria-hidden="true"></i>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    @include('includes.kh-contact-script')
@endpush
