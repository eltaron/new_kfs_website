@extends('layouts.app')

@section('title', 'تقييم خدمات المراكز التكنولوجية')

@push('css')
    <link rel="stylesheet" href="{{ asset('css') }}/survey.css">
    <style>
        .options-group-custom {
            flex-direction: row-reverse;
        }
    </style>
@endpush

@section('content')
    <main class="main-content bg-light">
        {{-- Page Header --}}
        <x-kfs.page-hero title="تقييم مستوى أداء الخدمات" subtitle="مشاركتك تساعدنا على تحسين جودة الخدمات المقدمة للمواطنين." icon="fa-solid fa-star-half-stroke" :image="asset('images/bg/survey.jpg')" />

        {{-- Progress Bar --}}
        <div class="progress-bar-container">
            <div class="progress-bar" id="surveyProgressBar" style="width: 0%;"></div>
        </div>

        <div class="container py-5">
            <form action="{{ route('surveys.service.store') }}" method="POST" class="survey-form">
                @csrf

                @if (session('success'))
                    <script>Swal.fire({ icon:'success', title:'تم بنجاح', text:{{ json_encode(session('success')) }}, confirmButtonText:'حسناً' });</script>
                @endif

                <p class="text-center mb-4"><strong>جميع الحقول التي تحتوي على هذه العلامة (<span
                            class="text-danger">*</span>) مطلوبة</strong></p>

                {{-- Personal Info Card --}}
                <div class="survey-card">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="center_name" class="form-label">اختر المركز التكنولوجي <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="center_name" name="center_name" required>
                                <option value="" disabled selected>-- اختر المركز --</option>
                                @isset($centers)
                                    @foreach ($centers as $center)
                                        <option value="{{ $center }}"
                                            {{ old('center_name') == $center ? 'selected' : '' }}>{{ $center }}</option>
                                    @endforeach
                                @endisset
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="age_group" class="form-label">الفئة العمرية <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="age_group" name="age_group" required>
                                <option value="أقل من 18" {{ old('age_group') == 'أقل من 18' ? 'selected' : '' }}>أقل من 18
                                </option>
                                <option value="18-30" {{ old('age_group') == '18-30' ? 'selected' : '' }}>18 - 30</option>
                                <option value="31-45" {{ old('age_group') == '31-45' ? 'selected' : '' }}>31 - 45</option>
                                <option value="46-60" {{ old('age_group') == '46-60' ? 'selected' : '' }}>46 - 60</option>
                                <option value="أكبر من 60" {{ old('age_group') == 'أكبر من 60' ? 'selected' : '' }}>أكبر من
                                    60</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="name" class="form-label">الاسم (اختياري)</label>
                            <input type="text" id="name" name="name" class="form-control"
                                value="{{ old('name') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="phone" class="form-label">رقم الهاتف (اختياري)</label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                value="{{ old('phone') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label d-block">الجنس <span class="text-danger">*</span></label>
                            <div class="form-check form-check-inline mt-2">
                                <input class="form-check-input" type="radio" name="gender" id="gender_male"
                                    value="ذكر" required {{ old('gender', 'ذكر') == 'ذكر' ? 'checked' : '' }}>
                                <label class="form-check-label" for="gender_male">ذكر</label>
                            </div>
                            <div class="form-check form-check-inline mt-2">
                                <input class="form-check-input" type="radio" name="gender" id="gender_female"
                                    value="أنثى" required {{ old('gender') == 'أنثى' ? 'checked' : '' }}>
                                <label class="form-check-label" for="gender_female">أنثى</label>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $questionGroups = [
                        'أولاً: مدى رضاك عن الخدمات المقدمة' => [
                            'q1_1_accessibility' => 'ما مدى رضاك عن سهولة الوصول إلى المراكز التكنولوجية؟',
                            'q1_2_procedure_clarity' => 'ما مدى رضاك عن وضوح إجراءات طلب الخدمة؟',
                            'q1_3_needs_fulfillment' => 'إلى أي مدى أنت راضٍ عن مدى تلبية الخدمات المقدمة لاحتياجاتك؟',
                            'q1_4_guidance' => 'إلى أي مدى أنت راضٍ عن الإرشاد حول استخدام بوابة الخدمات الحكومية؟',
                            'q1_5_staff_cooperation' => 'إلى أي مدى أنت راضٍ عن تعاون موظفي المركز معك؟',
                            'q1_6_process_handling' =>
                                'إلى أي مدى أنت راضٍ عن سهولة ووضوح الخطوات المتبعة داخل المراكز وكيفية التعامل معها؟',
                        ],
                        'ثانياً: تقييم سرعة تقديم الخدمات' => [
                            'q2_1_service_speed' => 'ما مدى رضاك عن سرعة تقديم الخدمة لك في المركز التكنولوجي؟',
                            'q2_2_wait_time' => 'إلى أي مدى أنت راضٍ عن وضوح المدة المتوقعة لإنجاز طلبك عند التقديم؟',
                            'q2_3_delay_justification' =>
                                'إلى أي مدى أنت راضٍ عن وضوح تبرير التأخير عند تأخر إنجاز الخدمة؟',
                        ],
                        'ثالثًا: تقييم أداء الموظفين' => [
                            'q3_1_staff_treatment' => 'إلى أي مدى أنت راضٍ عن تعامل موظفي المركز معك؟',
                            'q3_2_problem_solving' => 'إلى أي مدى أنت راضٍ عن اهتمام الموظفين بحل مشكلتك أو استفسارك؟',
                            'q3_3_communication_ease' => 'إلى أي مدى أنت راضٍ عن سهولة التواصل مع الموظفين؟',
                            'q3_4_fees_clarity' => 'إلى أي مدى أنت راضٍ عن وضوح وشفافية الرسوم المحصلة مقابل الخدمة؟',
                        ],
                        'رابعًا: تقييم بيئة المركز التكنولوجي' => [
                            'q4_1_cleanliness' => 'إلى أي مدى أنت راضٍ عن نظافة وتنظيم المركز التكنولوجي؟',
                            'q4_2_seating_comfort' => 'إلى أي مدى أنت راضٍ عن مناسبة وراحة أماكن الجلوس والانتظار؟',
                            'q4_3_accessibility_tools' =>
                                'إلى أي مدى أنت راضٍ عن الوسائل التكنولوجية المتاحة لذوي الهمم لتسهيل حصولهم على الخدمات؟',
                        ],
                    ];
                    $options = [5 => 'راضٍ جدًا', 4 => 'راضٍ', 3 => 'محايد', 2 => 'غير راضٍ', 1 => 'غير راضٍ تمامًا'];
                @endphp

                {{-- Looping through question groups --}}
                @foreach ($questionGroups as $groupTitle => $questions)
                    <div class="survey-card">
                        <h4 class="survey-group-title">{{ $groupTitle }}</h4>
                        @foreach ($questions as $key => $question)
                            {{-- Add data-question attribute here --}}
                            <div class="survey-question" data-question>
                                <label class="question-text">{{ $loop->parent->iteration }}.{{ $loop->iteration }}-
                                    {{ $question }} <span class="text-danger">*</span></label>
                                <div class="options-group-custom" role="group">
                                    @foreach (array_reverse($options, true) as $value => $label)
                                        <div class="option-item">
                                            {{-- Add visually-hidden class to the input --}}
                                            <input class="form-check-input visually-hidden" type="radio"
                                                name="{{ $key }}" id="{{ $key }}_{{ $value }}"
                                                value="{{ $value }}" {{ old($key) == $value ? 'checked' : '' }}
                                                required>
                                            <label class="form-check-label"
                                                for="{{ $key }}_{{ $value }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                @error($key)
                                    <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                @endforeach

                {{-- Open text questions card --}}
                <div class="survey-card">
                    <h4 class="survey-group-title">خامسًا: الاقتراحات والتوصيات</h4>
                    <div class="mb-4">
                        <label for="suggestions" class="question-text">هل لديك أي اقتراحات لتحسين الخدمات المقدمة في المراكز
                            التكنولوجية؟ (اختياري)</label>
                        <textarea name="suggestions" id="suggestions" class="form-control" rows="5" placeholder="اكتب اقتراحك هنا...">{{ old('suggestions') }}</textarea>
                    </div>

                    <h4 class="survey-group-title mt-5">شكوى من موظف معين (إن وجد)</h4>
                    <p class="text-muted mb-4">هذا الجزء اختياري ويستخدم فقط في حالة وجود شكوى محددة من موظف.</p>
                    <div class="mb-3">
                        <label for="complaint_employee_name" class="form-label">اسم الموظف</label>
                        <input type="text" id="complaint_employee_name" name="complaint_employee_name"
                            class="form-control" value="{{ old('complaint_employee_name') }}"
                            placeholder="ادخل اسم الموظف إن أمكن">
                    </div>
                    <div class="mb-3">
                        <label for="complaint_reason" class="form-label">الرجاء شرح سبب الشكوى</label>
                        <textarea name="complaint_reason" id="complaint_reason" class="form-control" rows="5"
                            placeholder="اشرح تفاصيل الموقف هنا...">{{ old('complaint_reason') }}</textarea>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5">إرسال التقييم</button>
                </div>
            </form>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Select only questions with radio buttons
            const questions = document.querySelectorAll('.survey-question[data-question]');
            const totalQuestions = questions.length;
            const progressBar = document.getElementById('surveyProgressBar');

            if (!progressBar || totalQuestions === 0) return;

            function updateProgress() {
                const answeredCount = document.querySelectorAll('.options-group-custom input[type="radio"]:checked')
                    .length;
                const progressPercentage = totalQuestions > 0 ? (answeredCount / totalQuestions) * 100 : 0;
                progressBar.style.width = progressPercentage + '%';
            }

            document.querySelectorAll('.options-group-custom input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    updateProgress();

                    const currentQuestion = this.closest('.survey-question');
                    let nextQuestion = currentQuestion.nextElementSibling;

                    // Find the next actual question, skipping other elements like titles
                    while (nextQuestion && !nextQuestion.matches(
                            '.survey-question[data-question]')) {
                        // If we reach the end of a card, check the next card
                        if (!nextQuestion.nextElementSibling && currentQuestion.closest(
                                '.survey-card').nextElementSibling) {
                            nextQuestion = currentQuestion.closest('.survey-card')
                                .nextElementSibling.querySelector(
                                    '.survey-question[data-question]');
                        } else {
                            nextQuestion = nextQuestion.nextElementSibling;
                        }
                    }

                    if (nextQuestion) {
                        setTimeout(() => {
                            nextQuestion.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }, 300);
                    }
                });
            });

            // Initial check on page load for `old()` values
            updateProgress();
        });
    </script>
@endpush
