@extends('layouts.app')
@section('title', 'تقييم خدمات المراكز التكنولوجية')
@section('description', 'قيّم مستوى الخدمة في المراكز التكنولوجية بمحافظة كفر الشيخ — رأيك يساعدنا على تحسين جودة الخدمات.')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/kfs-help.css') }}?v={{ @filemtime(public_path('css/kfs-help.css')) }}">
@endpush

@section('content')
    @php
        $questionGroups = [
            ['fa-solid fa-hand-holding-heart', 'مدى رضاك عن الخدمات المقدمة', [
                'q1_1_accessibility' => 'سهولة الوصول إلى المراكز التكنولوجية',
                'q1_2_procedure_clarity' => 'وضوح إجراءات طلب الخدمة',
                'q1_3_needs_fulfillment' => 'مدى تلبية الخدمات المقدمة لاحتياجاتك',
                'q1_4_guidance' => 'الإرشاد حول استخدام بوابة الخدمات الحكومية',
                'q1_5_staff_cooperation' => 'تعاون موظفي المركز معك',
                'q1_6_process_handling' => 'سهولة ووضوح الخطوات المتبعة داخل المركز',
            ]],
            ['fa-solid fa-gauge-high', 'سرعة تقديم الخدمات', [
                'q2_1_service_speed' => 'سرعة تقديم الخدمة لك في المركز',
                'q2_2_wait_time' => 'وضوح المدة المتوقعة لإنجاز طلبك عند التقديم',
                'q2_3_delay_justification' => 'وضوح تبرير التأخير عند تأخر إنجاز الخدمة',
            ]],
            ['fa-solid fa-user-tie', 'أداء الموظفين', [
                'q3_1_staff_treatment' => 'تعامل موظفي المركز معك',
                'q3_2_problem_solving' => 'اهتمام الموظفين بحل مشكلتك أو استفسارك',
                'q3_3_communication_ease' => 'سهولة التواصل مع الموظفين',
                'q3_4_fees_clarity' => 'وضوح وشفافية الرسوم المحصلة مقابل الخدمة',
            ]],
            ['fa-solid fa-building', 'بيئة المركز التكنولوجي', [
                'q4_1_cleanliness' => 'نظافة وتنظيم المركز',
                'q4_2_seating_comfort' => 'مناسبة وراحة أماكن الجلوس والانتظار',
                'q4_3_accessibility_tools' => 'الوسائل المتاحة لذوي الهمم لتسهيل حصولهم على الخدمات',
            ]],
        ];
        $scale = [
            5 => ['fa-regular fa-face-grin-stars', 'راضٍ جدًا'],
            4 => ['fa-regular fa-face-smile', 'راضٍ'],
            3 => ['fa-regular fa-face-meh', 'محايد'],
            2 => ['fa-regular fa-face-frown', 'غير راضٍ'],
            1 => ['fa-regular fa-face-angry', 'غير راضٍ تمامًا'],
        ];
        $ages = ['أقل من 18' => 'أقل من 18', '18-30' => '18 – 30', '31-45' => '31 – 45', '46-60' => '46 – 60', 'أكبر من 60' => 'أكبر من 60'];
        $totalQ = collect($questionGroups)->sum(fn ($g) => count($g[2]));
    @endphp

    <main class="kh kh--support kh--survey">
        <x-kfs.page-hero size="compact" title="تقييم خدمات المراكز التكنولوجية" kicker="رأيك يهمنا" icon="fa-solid fa-star-half-stroke"
            :image="asset('images/bg/survey.jpg')"
            subtitle="{{ $totalQ }} سؤالاً سريعاً بالضغط على الوجه المناسب — أقل من 3 دقائق، ويساعدنا على تحسين الخدمة." />

        <div class="kh-progress" id="khProgress" aria-hidden="true">
            <div class="container kh-progress__in">
                <span class="kh-progress__txt"><strong id="khAnswered">0</strong> من {{ $totalQ }} سؤال</span>
                <span class="kh-progress__bar"><span id="khBar"></span></span>
            </div>
        </div>

        <div class="container kh__wrap kh-survey">
            @if (session('success'))
                <section class="kh-done" role="status" aria-live="polite">
                    <span class="kh-done__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                    <div class="kh-done__body">
                        <h2>شكراً لك!</h2>
                        <p>{{ session('success') }}</p>
                    </div>
                    <a href="{{ route('home') }}" class="kh-btn kh-btn--ghost">العودة للرئيسية</a>
                </section>
            @endif

            @if ($errors->any())
                <section class="kh-errors" role="alert">
                    <h2><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> يرجى استكمال الأسئلة التالية</h2>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <form action="{{ route('surveys.service.store') }}" method="POST" class="kh-form" id="khSurvey">
                @csrf

                {{-- Center + respondent --}}
                <fieldset class="kh-card">
                    <legend class="kh-card__title"><span class="kh-card__num">1</span> المركز وبياناتك</legend>

                    <span class="kh-label" id="centerLbl">المركز التكنولوجي الذي زرته <span class="kh-req">*</span></span>
                    <div class="kh-centers" role="radiogroup" aria-labelledby="centerLbl">
                        @foreach ($centers ?? [] as $center)
                            <label class="kh-center">
                                <input type="radio" name="center_name" value="{{ $center }}" required @checked(old('center_name') === $center)>
                                <span><i class="fa-solid fa-building-circle-check" aria-hidden="true"></i>{{ \Illuminate\Support\Str::after($center, 'بمدينة ') ?: $center }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('center_name')<p class="kh-field-error">{{ $message }}</p>@enderror

                    <div class="kh-grid kh-survey__who">
                        <div class="kh-field">
                            <span class="kh-label" id="ageLbl">الفئة العمرية <span class="kh-req">*</span></span>
                            <div class="kh-pills" role="radiogroup" aria-labelledby="ageLbl">
                                @foreach ($ages as $val => $label)
                                    <label><input type="radio" name="age_group" value="{{ $val }}" required @checked(old('age_group', '18-30') === $val)><span>{{ $label }}</span></label>
                                @endforeach
                            </div>
                        </div>
                        <div class="kh-field">
                            <span class="kh-label" id="genderLbl">النوع <span class="kh-req">*</span></span>
                            <div class="kh-seg" role="radiogroup" aria-labelledby="genderLbl">
                                <label><input type="radio" name="gender" value="ذكر" required @checked(old('gender', 'ذكر') === 'ذكر')><span><i class="fa-solid fa-mars" aria-hidden="true"></i> ذكر</span></label>
                                <label><input type="radio" name="gender" value="أنثى" required @checked(old('gender') === 'أنثى')><span><i class="fa-solid fa-venus" aria-hidden="true"></i> أنثى</span></label>
                            </div>
                        </div>
                        <div class="kh-field">
                            <label for="name" class="kh-label">الاسم <span class="kh-opt">(اختياري)</span></label>
                            <input type="text" id="name" name="name" class="kh-input" maxlength="255" autocomplete="name" value="{{ old('name') }}">
                        </div>
                        <div class="kh-field">
                            <label for="phone" class="kh-label">رقم الهاتف <span class="kh-opt">(اختياري)</span></label>
                            <input type="tel" id="phone" name="phone" class="kh-input" dir="ltr" inputmode="tel" maxlength="20" autocomplete="tel"
                                value="{{ old('phone') }}" placeholder="01XXXXXXXXX">
                        </div>
                    </div>
                </fieldset>

                {{-- Rating groups --}}
                @foreach ($questionGroups as [$gIcon, $gTitle, $questions])
                    @php $gNo = $loop->iteration; @endphp
                    <fieldset class="kh-card">
                        <legend class="kh-card__title"><span class="kh-card__num">{{ $gNo + 1 }}</span> {{ $gTitle }}</legend>
                        <p class="kh-survey__lead"><i class="{{ $gIcon }}" aria-hidden="true"></i> ما مدى رضاك عن…</p>
                        <div class="kh-qs">
                            @foreach ($questions as $key => $q)
                                <div class="kh-q @error($key) is-missing @enderror" data-q role="radiogroup" aria-labelledby="{{ $key }}_lbl">
                                    <p class="kh-q__text" id="{{ $key }}_lbl"><span class="kh-q__no">{{ $gNo }}.{{ $loop->iteration }}</span> {{ $q }} <span class="kh-req">*</span></p>
                                    <div class="kh-rate">
                                        @foreach ($scale as $val => [$icon, $label])
                                            <label class="kh-rate__opt kh-rate__opt--{{ $val }}">
                                                <input type="radio" name="{{ $key }}" value="{{ $val }}" required @checked((string) old($key) === (string) $val)>
                                                <span><i class="{{ $icon }}" aria-hidden="true"></i><small>{{ $label }}</small></span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                {{-- Open questions --}}
                <fieldset class="kh-card">
                    <legend class="kh-card__title"><span class="kh-card__num">{{ count($questionGroups) + 2 }}</span> اقتراحاتك <span class="kh-opt">(اختياري)</span></legend>
                    <div class="kh-field">
                        <label for="suggestions" class="kh-label">هل لديك اقتراحات لتحسين الخدمات في المراكز التكنولوجية؟</label>
                        <textarea name="suggestions" id="suggestions" class="kh-input" rows="4" maxlength="5000" placeholder="اكتب اقتراحك هنا…">{{ old('suggestions') }}</textarea>
                    </div>

                    <details class="kh-complaint" @if (old('complaint_employee_name') || old('complaint_reason')) open @endif>
                        <summary><i class="fa-solid fa-user-xmark" aria-hidden="true"></i> لديك شكوى من موظف معيّن؟</summary>
                        <div class="kh-grid">
                            <div class="kh-field kh-field--full">
                                <label for="complaint_employee_name" class="kh-label">اسم الموظف</label>
                                <input type="text" id="complaint_employee_name" name="complaint_employee_name" class="kh-input" maxlength="255"
                                    value="{{ old('complaint_employee_name') }}" placeholder="إن أمكن">
                            </div>
                            <div class="kh-field kh-field--full">
                                <label for="complaint_reason" class="kh-label">سبب الشكوى</label>
                                <textarea name="complaint_reason" id="complaint_reason" class="kh-input" rows="4" maxlength="5000"
                                    placeholder="اشرح تفاصيل الموقف…">{{ old('complaint_reason') }}</textarea>
                            </div>
                        </div>
                    </details>
                </fieldset>

                <div class="kh-submit">
                    <button type="submit" class="kh-btn kh-btn--accent kh-btn--lg" id="khSubmit">
                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>إرسال التقييم</span>
                    </button>
                    <p><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> التقييم سري ويُستخدم لتحسين جودة الخدمة فقط.</p>
                </div>
            </form>
        </div>
    </main>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('khSurvey');
    const qs = [...form.querySelectorAll('[data-q]')];
    const answered = document.getElementById('khAnswered');
    const bar = document.getElementById('khBar');
    const isDone = q => !!q.querySelector('input:checked');
    const update = () => {
        const n = qs.filter(isDone).length;
        answered.textContent = n;
        bar.style.width = (n / qs.length * 100) + '%';
        document.getElementById('khProgress').classList.toggle('is-complete', n === qs.length);
    };

    qs.forEach((q, i) => q.addEventListener('change', () => {
        q.classList.remove('is-missing');
        update();
        const next = qs.slice(i + 1).find(x => !isDone(x));
        if (next) setTimeout(() => next.scrollIntoView({ behavior: 'smooth', block: 'center' }), 250);
    }));

    // Native validation fires before submit: mark the unanswered questions it stops on.
    form.addEventListener('invalid', e => { e.target.closest('[data-q]')?.classList.add('is-missing'); }, true);

    form.addEventListener('submit', e => {
        const missing = qs.filter(q => !isDone(q));
        if (missing.length) {
            e.preventDefault();
            missing.forEach(q => q.classList.add('is-missing'));
            missing[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            missing[0].querySelector('input').focus({ preventScroll: true });
            return;
        }
        const btn = document.getElementById('khSubmit');
        btn.disabled = true; btn.querySelector('span').textContent = 'جاري الإرسال…';
    });
    update();
})();
</script>
@endpush
