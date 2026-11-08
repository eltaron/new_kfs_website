@extends('layouts.app')
@section('title', 'تقديم مقترح')
@section('description', 'شارك محافظة كفر الشيخ بأفكارك ومقترحاتك لتطوير الخدمات والمرافق والموقع الإلكتروني.')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/kfs-help.css') }}?v={{ @filemtime(public_path('css/kfs-help.css')) }}">
@endpush

@section('content')
    @php
        $topics = [
            ['fa-solid fa-building-columns', 'تطوير الخدمات الحكومية'],
            ['fa-solid fa-road', 'الطرق والمرافق'],
            ['fa-solid fa-leaf', 'النظافة والبيئة'],
            ['fa-solid fa-heart-pulse', 'التعليم والصحة'],
            ['fa-solid fa-chart-line', 'الاستثمار والتنمية'],
            ['fa-solid fa-laptop', 'تطوير الموقع الإلكتروني'],
        ];
        $user = auth()->user();
        $flow = [
            ['fa-solid fa-inbox', 'نستلم مقترحك', 'ويصل فوراً لفريق المتابعة'],
            ['fa-solid fa-magnifying-glass-chart', 'تتم دراسته', 'مع الجهة المختصة بموضوعه'],
            ['fa-solid fa-phone', 'نتواصل معك', 'عند الحاجة لتفاصيل إضافية'],
        ];
    @endphp

    <main class="kh kh--idea">
        <x-kfs.page-hero size="compact" title="تقديم مقترح" kicker="شاركنا التطوير" icon="fa-solid fa-lightbulb"
            :image="asset('images/bg/suggestion.jpg')" :crumbs="Route::has('requests.index') ? ['الشكاوى والبلاغات' => route('requests.index')] : []"
            subtitle="عندك فكرة تحسّن خدمة أو مرفقاً في المحافظة؟ شاركنا بها — كل مقترح يُقرأ ويُدرس." />

        <div class="container kh__wrap">
            @if (session('success'))
                <section class="kh-done" role="status" aria-live="polite">
                    <span class="kh-done__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                    <div class="kh-done__body">
                        <h2>شكراً لمشاركتك!</h2>
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
                <form action="{{ route('suggestions.store') }}" method="POST" class="kh-form" id="khForm">
                    @csrf

                    {{-- 1. Idea --}}
                    <fieldset class="kh-card">
                        <legend class="kh-card__title"><span class="kh-card__num">1</span> في أي مجال مقترحك؟</legend>
                        <div class="kh-topics" role="group" aria-label="اختر مجالاً">
                            @foreach ($topics as [$icon, $label])
                                <button type="button" class="kh-topic" data-topic="{{ $label }}" aria-pressed="false">
                                    <i class="{{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
                                </button>
                            @endforeach
                        </div>
                        <div class="kh-field">
                            <label for="subject" class="kh-label">عنوان المقترح <span class="kh-req">*</span></label>
                            <input type="text" id="subject" name="subject" class="kh-input" maxlength="255" required
                                value="{{ old('subject') }}" placeholder="اختر مجالاً من الأعلى أو اكتب عنواناً مختصراً لفكرتك">
                            @error('subject')<p class="kh-field-error">{{ $message }}</p>@enderror
                        </div>
                    </fieldset>

                    {{-- 2. Details --}}
                    <fieldset class="kh-card">
                        <legend class="kh-card__title"><span class="kh-card__num">2</span> اشرح فكرتك</legend>
                        <div class="kh-field">
                            <label for="message" class="kh-label">تفاصيل المقترح <span class="kh-req">*</span></label>
                            <textarea id="message" name="message" class="kh-input" rows="7" maxlength="5000" required
                                placeholder="ما الفكرة؟ ما المشكلة التي تحلها؟ وأين يمكن تطبيقها؟">{{ old('message') }}</textarea>
                            <div class="kh-field-foot">
                                @error('message')<p class="kh-field-error">{{ $message }}</p>@else<span>الفكرة الواضحة والمحددة أسهل في الدراسة والتنفيذ.</span>@enderror
                                <small class="kh-count" id="khCount" aria-live="polite">0 / 5000</small>
                            </div>
                        </div>
                    </fieldset>

                    {{-- 3. Person --}}
                    <fieldset class="kh-card">
                        <legend class="kh-card__title"><span class="kh-card__num">3</span> بياناتك</legend>
                        <div class="kh-grid">
                            <div class="kh-field">
                                <label for="name" class="kh-label">الاسم الكامل <span class="kh-req">*</span></label>
                                <input type="text" id="name" name="name" class="kh-input" maxlength="255" required autocomplete="name"
                                    value="{{ old('name', $user?->name) }}">
                                @error('name')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="kh-field">
                                <label for="national_id" class="kh-label">الرقم القومي <span class="kh-req">*</span></label>
                                <input type="text" id="national_id" name="national_id" class="kh-input" dir="ltr" inputmode="numeric"
                                    pattern="\d{14}" maxlength="14" title="14 رقماً" required placeholder="14 رقماً"
                                    value="{{ old('national_id', $user?->national_id) }}">
                                @error('national_id')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="kh-field">
                                <label for="phone" class="kh-label">رقم الهاتف <span class="kh-opt">(اختياري)</span></label>
                                <input type="tel" id="phone" name="phone" class="kh-input" dir="ltr" inputmode="tel" maxlength="20" autocomplete="tel"
                                    value="{{ old('phone', $user?->phone) }}" placeholder="01XXXXXXXXX">
                                @error('phone')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="kh-field">
                                <label for="email" class="kh-label">البريد الإلكتروني <span class="kh-opt">(اختياري)</span></label>
                                <input type="email" id="email" name="email" class="kh-input" dir="ltr" maxlength="255" autocomplete="email"
                                    value="{{ old('email', $user?->email) }}" placeholder="name@example.com">
                                @error('email')<p class="kh-field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <p class="kh-hint"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> نستخدم بياناتك للتواصل بشأن المقترح فقط.</p>
                    </fieldset>

                    <div class="kh-submit">
                        <button type="submit" class="kh-btn kh-btn--accent kh-btn--lg" id="khSubmit">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>إرسال المقترح</span>
                        </button>
                        <p><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> بياناتك مؤمّنة ويتم التعامل معها بسرية تامة.</p>
                    </div>
                </form>

                <aside class="kh-side" aria-label="معلومات عن المقترحات">
                    <div class="kh-side__card kh-side__card--brand">
                        <span class="kh-side__badge" aria-hidden="true"><i class="fa-solid fa-lightbulb"></i></span>
                        <h2>ماذا يحدث لمقترحك؟</h2>
                        <ol class="kh-flow">
                            @foreach ($flow as [$icon, $title, $sub])
                                <li>
                                    <span class="kh-flow__icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                                    <span><strong>{{ $title }}</strong><small>{{ $sub }}</small></span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                    <div class="kh-side__card">
                        <h2><i class="fa-solid fa-star" aria-hidden="true"></i> مقترح أقوى</h2>
                        <ul>
                            <li>ركّز على فكرة واحدة في كل مقترح.</li>
                            <li>اذكر المكان أو الخدمة المقصودة بالتحديد.</li>
                            <li>وضّح الفائدة المتوقعة للمواطنين.</li>
                        </ul>
                    </div>
                    @if (Route::has('requests.index'))
                        <a href="{{ route('requests.index') }}" class="kh-route kh-route--card">
                            <span class="kh-route__icon" aria-hidden="true"><i class="fa-solid fa-file-pen"></i></span>
                            <span class="kh-route__body"><strong>عندك شكوى وليس مقترحاً؟</strong><small>قدّمها عبر منظومة الشكاوى</small></span>
                            <i class="fa-solid fa-arrow-left kh-route__go" aria-hidden="true"></i>
                        </a>
                    @endif
                </aside>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    @include('includes.kh-contact-script')
@endpush
