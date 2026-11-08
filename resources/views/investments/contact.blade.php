@extends('layouts.app')
@section('title', 'تواصل مع قطاع الاستثمار')
@section('description', 'أرسل استفسارك إلى إدارة الاستثمار بمحافظة كفر الشيخ حول الفرص الاستثمارية والمناطق الصناعية وتخصيص الأراضي.')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/kfs-help.css') }}?v={{ @filemtime(public_path('css/kfs-help.css')) }}">
@endpush

@section('content')
    @php
        $topics = [
            ['fa-solid fa-chart-line', 'استفسار عن فرصة استثمارية'],
            ['fa-solid fa-industry', 'المناطق الصناعية'],
            ['fa-solid fa-map', 'تخصيص الأراضي'],
            ['fa-solid fa-file-signature', 'التراخيص والإجراءات'],
            ['fa-solid fa-handshake', 'شراكة مع المحافظة'],
            ['fa-solid fa-ellipsis', 'استفسار آخر'],
        ];
        $user = auth()->user();
        $links = [
            ['fa-solid fa-briefcase', 'الفرص الاستثمارية', 'الفرص المتاحة حالياً', Route::has('investments.index') ? route('investments.index') : null],
            ['fa-solid fa-chart-pie', 'الخطة الاستثمارية', 'خطط ومشروعات المحافظة', Route::has('investment.plans.index') ? route('investment.plans.index') : null],
            ['fa-solid fa-map-location-dot', 'الخريطة التفاعلية', 'مواقع المشروعات على الخريطة', Route::has('governorate.map') ? route('governorate.map', ['group' => 'project']) : null],
        ];
    @endphp

    <main class="kh kh--invest">
        <x-kfs.page-hero size="compact" title="تواصل مع قطاع الاستثمار" kicker="الاستثمار" icon="fa-solid fa-handshake"
            :image="asset('images/bg/investment.jpg')" :crumbs="['الفرص الاستثمارية' => route('investments.index')]"
            subtitle="نجيب عن استفساراتكم حول الفرص الاستثمارية والمناطق الصناعية وتخصيص الأراضي في المحافظة." />

        <div class="container kh__wrap">
            @if (session('success'))
                <section class="kh-done" role="status" aria-live="polite">
                    <span class="kh-done__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                    <div class="kh-done__body">
                        <h2>تم استلام استفساركم</h2>
                        <p>{{ session('success') }}</p>
                    </div>
                    <a href="{{ route('investments.index') }}" class="kh-btn kh-btn--ghost">تصفح الفرص الاستثمارية</a>
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
                <form action="{{ route('investment.contact.store') }}" method="POST" class="kh-form" id="khForm">
                    @csrf

                    {{-- 1. Investor --}}
                    <fieldset class="kh-card">
                        <legend class="kh-card__title"><span class="kh-card__num">1</span> بيانات المستثمر</legend>
                        <div class="kh-grid">
                            <div class="kh-field">
                                <label for="name" class="kh-label">الاسم بالكامل <span class="kh-req">*</span></label>
                                <input type="text" id="name" name="name" class="kh-input" maxlength="255" required autocomplete="name"
                                    value="{{ old('name', $user?->name) }}">
                                @error('name')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="kh-field">
                                <label for="company_name" class="kh-label">اسم الشركة <span class="kh-opt">(إن وجد)</span></label>
                                <input type="text" id="company_name" name="company_name" class="kh-input" maxlength="255" autocomplete="organization"
                                    value="{{ old('company_name') }}">
                                @error('company_name')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="kh-field">
                                <label for="email" class="kh-label">البريد الإلكتروني <span class="kh-req">*</span></label>
                                <input type="email" id="email" name="email" class="kh-input" dir="ltr" maxlength="255" required autocomplete="email"
                                    value="{{ old('email', $user?->email) }}" placeholder="name@company.com">
                                @error('email')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="kh-field">
                                <label for="phone" class="kh-label">رقم الهاتف <span class="kh-req">*</span></label>
                                <input type="tel" id="phone" name="phone" class="kh-input" dir="ltr" inputmode="tel" maxlength="20" required autocomplete="tel"
                                    value="{{ old('phone', $user?->phone) }}" placeholder="01XXXXXXXXX">
                                @error('phone')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </fieldset>

                    {{-- 2. Inquiry --}}
                    <fieldset class="kh-card">
                        <legend class="kh-card__title"><span class="kh-card__num">2</span> موضوع الاستفسار</legend>
                        <div class="kh-topics" role="group" aria-label="اختر موضوعاً">
                            @foreach ($topics as [$icon, $label])
                                <button type="button" class="kh-topic" data-topic="{{ $label }}" aria-pressed="false">
                                    <i class="{{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
                                </button>
                            @endforeach
                        </div>
                        <div class="kh-grid">
                            <div class="kh-field kh-field--full">
                                <label for="subject" class="kh-label">عنوان الاستفسار <span class="kh-req">*</span></label>
                                <input type="text" id="subject" name="subject" class="kh-input" maxlength="255" required
                                    value="{{ old('subject') }}" placeholder="مثال: استفسار عن المنطقة الصناعية بمطوبس">
                                @error('subject')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="kh-field kh-field--full">
                                <label for="message" class="kh-label">رسالتك <span class="kh-req">*</span></label>
                                <textarea id="message" name="message" class="kh-input" rows="6" minlength="10" required
                                    placeholder="نوع النشاط، المساحة المطلوبة، الموقع المفضل، حجم الاستثمار المتوقع…">{{ old('message') }}</textarea>
                                <div class="kh-field-foot">
                                    @error('message')<p class="kh-field-error">{{ $message }}</p>@else<span>10 أحرف على الأقل.</span>@enderror
                                    <small class="kh-count" id="khCount" aria-live="polite">0</small>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <div class="kh-submit">
                        <button type="submit" class="kh-btn kh-btn--accent kh-btn--lg" id="khSubmit">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>إرسال الاستفسار</span>
                        </button>
                        <p><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> يراجع المكتب الفني لإدارة الاستثمار رسالتك ويتواصل معك.</p>
                    </div>
                </form>

                <aside class="kh-side" aria-label="معلومات قطاع الاستثمار">
                    <div class="kh-side__card kh-side__card--brand">
                        <span class="kh-side__badge" aria-hidden="true"><i class="fa-solid fa-building-columns"></i></span>
                        <h2>إدارة الاستثمار</h2>
                        <dl class="kh-facts">
                            <div><dt><i class="fa-solid fa-location-dot" aria-hidden="true"></i> المقر</dt><dd>ديوان عام محافظة كفر الشيخ</dd></div>
                            <div><dt><i class="fa-solid fa-clock" aria-hidden="true"></i> أوقات العمل</dt><dd>الأحد – الخميس · 9 ص – 3 م</dd></div>
                            <div><dt><i class="fa-solid fa-user-tie" aria-hidden="true"></i> المتابعة</dt><dd>المكتب الفني للاستثمار</dd></div>
                        </dl>
                    </div>
                    <div class="kh-side__card">
                        <h2><i class="fa-solid fa-compass" aria-hidden="true"></i> قبل أن تراسلنا</h2>
                        <div class="kh-routes">
                            @foreach ($links as [$icon, $title, $sub, $url])
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
