@extends('layouts.app')
@section('title', 'التقديم على برنامج: ' . $program->title)
@push('css')
    <link rel="stylesheet" href="{{ asset('css/estidama-portal.css') }}?v={{ @filemtime(public_path('css/estidama-portal.css')) }}">
@endpush

@php
    $sections = [
        'es-sec-personal' => 'بيانات المتقدم',
        'es-sec-work' => 'معلومات العمل',
        'es-sec-files' => 'المرفقات',
        'es-sec-extra' => 'أسئلة إضافية',
    ];
    $fieldError = fn ($name) => $errors->has($name) ? ' is-invalid' : '';
@endphp

@section('content')
    <main class="es-page">
        <x-kfs.page-hero :title="$program->title" kicker="تقديم طلب تسجيل" icon="fa-solid fa-file-pen" size="compact"
            subtitle="يرجى ملء النموذج التالي للتقديم على البرنامج التدريبي."
            :crumbs="['استدامة' => route('estidama.index'), 'البرامج التدريبية' => route('estidama.programs'), \Illuminate\Support\Str::limit($program->title, 40) => route('estidama.program.show', $program)]"
            current="طلب الالتحاق" />

        <div class="container">
            @if (session('success'))
                {{-- Success receipt (the form is hidden after a successful submission, as before) --}}
                <script>Swal.fire({ icon:'success', title:'تم بنجاح', text:{{ json_encode(session('success')) }}, confirmButtonText:'حسناً' });</script>
                <div class="es-narrow">
                    <div class="es-card es-receipt" role="status">
                        <div class="es-receipt__top">
                            <div class="es-receipt__badge" aria-hidden="true"><i class="fa-solid fa-check"></i></div>
                            <h2>تم استلام طلبك بنجاح</h2>
                            <p>{{ session('success') }}</p>
                            <div class="es-receipt__program"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> {{ $program->title }}</div>
                        </div>
                        <div class="es-receipt__next">
                            <h3>ماذا بعد؟</h3>
                            <ol>
                                <li><span class="es-ico" aria-hidden="true"><i class="fa-solid fa-user-check"></i></span><span>تراجع إدارة المركز بياناتك ومرفقاتك.</span></li>
                                <li><span class="es-ico" aria-hidden="true"><i class="fa-solid fa-bell"></i></span><span>يصلك إشعار بنتيجة المراجعة وتظهر حالة الطلب في «تعلّمي».</span></li>
                                <li><span class="es-ico" aria-hidden="true"><i class="fa-solid fa-circle-play"></i></span><span>عند القبول يُفتح لك محتوى البرنامج{{ $program->isFree() ? '' : ' بعد سداد الرسوم' }}.</span></li>
                            </ol>
                        </div>
                        <div class="es-actions">
                            <a href="{{ route('lms.dashboard') }}" class="es-btn"><i class="fa-solid fa-book-open-reader" aria-hidden="true"></i> متابعة طلباتي في «تعلّمي»</a>
                            <a href="{{ route('estidama.programs') }}" class="es-btn es-btn--ghost">تصفح برامج أخرى</a>
                        </div>
                    </div>
                </div>
            @else
                <div class="es-layout">
                    <div>
                        <ol class="es-card es-stepper" aria-label="أقسام النموذج">
                            @foreach ($sections as $anchor => $label)
                                <li class="{{ $loop->first ? 'is-active' : '' }}">
                                    <a href="#{{ $anchor }}"><span class="es-step-n">{{ $loop->iteration }}</span><span class="es-step-l">{{ $label }}</span></a>
                                </li>
                            @endforeach
                        </ol>

                        <form action="{{ route('estidama.storeApplication', $program) }}" method="POST" enctype="multipart/form-data"
                            class="survey-form">
                            @csrf

                            @if ($errors->any())
                                <div class="es-alert es-alert--error" role="alert">
                                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                    <div>
                                        <strong>خطأ!</strong> يرجى مراجعة الحقول وإصلاح الأخطاء.
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif

                            {{-- 1. Personal info --}}
                            <section class="es-card es-form" id="es-sec-personal" aria-labelledby="esSecPersonal">
                                <h2 class="es-form__title" id="esSecPersonal"><span class="es-ico" aria-hidden="true"><i class="fa-solid fa-id-card"></i></span> بيانات المتقدم</h2>
                                <p class="es-form__hint">جميع الحقول التي تحتوي على العلامة (<span class="es-req">*</span>) مطلوبة.</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="applicant_name" class="form-label">الاسم الكامل (رباعي) <span class="es-req">*</span></label>
                                        <input type="text" id="applicant_name" name="applicant_name" class="form-control{{ $fieldError('applicant_name') }}"
                                            value="{{ old('applicant_name') }}" autocomplete="name" required>
                                        @error('applicant_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="national_id" class="form-label">الرقم القومي (14 رقم) <span class="es-req">*</span></label>
                                        <input type="text" id="national_id" name="national_id" class="form-control{{ $fieldError('national_id') }}" dir="ltr"
                                            inputmode="numeric" maxlength="14" pattern="\d{14}" title="يجب إدخال 14 رقمًا" value="{{ old('national_id') }}" required>
                                        @error('national_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">رقم الهاتف <span class="es-req">*</span></label>
                                        <input type="tel" id="phone" name="phone" class="form-control{{ $fieldError('phone') }}" dir="ltr"
                                            value="{{ old('phone') }}" autocomplete="tel" required>
                                        @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="applicant_email" class="form-label">البريد الإلكتروني <span class="es-req">*</span></label>
                                        <input type="email" id="applicant_email" name="applicant_email" class="form-control{{ $fieldError('applicant_email') }}" dir="ltr"
                                            value="{{ old('applicant_email') }}" autocomplete="email" required>
                                        @error('applicant_email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <fieldset class="es-fieldset">
                                            <legend>الجنس <span class="es-req">*</span></legend>
                                            <div class="es-choices">
                                                <div class="es-choice">
                                                    <input type="radio" name="gender" id="gender_male" value="ذكر" {{ old('gender') == 'ذكر' ? 'checked' : '' }} required>
                                                    <label for="gender_male">ذكر</label>
                                                </div>
                                                <div class="es-choice">
                                                    <input type="radio" name="gender" id="gender_female" value="أنثى" {{ old('gender') == 'أنثى' ? 'checked' : '' }} required>
                                                    <label for="gender_female">أنثى</label>
                                                </div>
                                            </div>
                                            @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </fieldset>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="educational_qualification" class="form-label">المؤهل الدراسي <span class="es-req">*</span></label>
                                        <input type="text" id="educational_qualification" name="educational_qualification"
                                            class="form-control{{ $fieldError('educational_qualification') }}" value="{{ old('educational_qualification') }}" required>
                                        @error('educational_qualification')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="specialization" class="form-label">التخصص الدراسي</label>
                                        <input type="text" id="specialization" name="specialization" class="form-control{{ $fieldError('specialization') }}"
                                            value="{{ old('specialization') }}">
                                        @error('specialization')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label for="highest_degree" class="form-label">أعلى مؤهل دراسات عليا (إن وجد)</label>
                                        <input type="text" id="highest_degree" name="highest_degree" class="form-control{{ $fieldError('highest_degree') }}"
                                            value="{{ old('highest_degree') }}">
                                        @error('highest_degree')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </section>

                            {{-- 2. Work --}}
                            <section class="es-card es-form" id="es-sec-work" aria-labelledby="esSecWork">
                                <h2 class="es-form__title" id="esSecWork"><span class="es-ico" aria-hidden="true"><i class="fa-solid fa-briefcase"></i></span> معلومات العمل</h2>
                                <p class="es-form__hint">بيانات جهة العمل الحالية كما ستظهر في سجل المتدرب.</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="employment_status" class="form-label">جهة العمل <span class="es-req">*</span></label>
                                        <input type="text" id="employment_status" name="employment_status"
                                            class="form-control{{ $fieldError('employment_status') }}" value="{{ old('employment_status') }}" required>
                                        @error('employment_status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="current_position" class="form-label">الوظيفة الحالية <span class="es-req">*</span></label>
                                        <input type="text" id="current_position" name="current_position" class="form-control{{ $fieldError('current_position') }}"
                                            value="{{ old('current_position') }}" required>
                                        @error('current_position')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="job_address" class="form-label">عنوان الوظيفة <span class="es-req">*</span></label>
                                        <input type="text" id="job_address" name="job_address" class="form-control{{ $fieldError('job_address') }}"
                                            value="{{ old('job_address') }}" required>
                                        @error('job_address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </section>

                            {{-- 3. Attachments --}}
                            <section class="es-card es-form" id="es-sec-files" aria-labelledby="esSecFiles">
                                <h2 class="es-form__title" id="esSecFiles"><span class="es-ico" aria-hidden="true"><i class="fa-solid fa-paperclip"></i></span> المرفقات</h2>
                                <p class="es-form__hint">صور واضحة بصيغة JPG أو PNG أو WEBP، والحد الأقصى لكل ملف 2MB.</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="national_id_front_image" class="form-label">صورة البطاقة (الوجه الأمامي) <span class="es-req">*</span></label>
                                        <input type="file" id="national_id_front_image" name="national_id_front_image" accept="image/jpeg,image/png,image/webp"
                                            class="form-control{{ $fieldError('national_id_front_image') }}" required>
                                        <small class="form-text">الحد الأقصى للحجم: 2MB.</small>
                                        @error('national_id_front_image')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="national_id_back_image" class="form-label">صورة البطاقة (الوجه الخلفي) <span class="es-req">*</span></label>
                                        <input type="file" id="national_id_back_image" name="national_id_back_image" accept="image/jpeg,image/png,image/webp"
                                            class="form-control{{ $fieldError('national_id_back_image') }}" required>
                                        <small class="form-text">الحد الأقصى للحجم: 2MB.</small>
                                        @error('national_id_back_image')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="personal_statement" class="form-label">بيان حالة مدون (اختياري)</label>
                                        <input type="file" id="personal_statement" name="personal_statement" accept="application/pdf,.pdf"
                                            class="form-control{{ $fieldError('personal_statement') }}">
                                        <small class="form-text">النوع المسموح به: PDF. الحد الأقصى للحجم: 2MB.</small>
                                        @error('personal_statement')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </section>

                            {{-- 4. Extra questions --}}
                            <section class="es-card es-form" id="es-sec-extra" aria-labelledby="esSecExtra">
                                <h2 class="es-form__title" id="esSecExtra"><span class="es-ico" aria-hidden="true"><i class="fa-solid fa-circle-question"></i></span> أسئلة إضافية</h2>
                                <p class="es-form__hint">تساعدنا إجابتك على تقدير خبراتك التدريبية السابقة.</p>
                                <fieldset class="es-fieldset">
                                    <legend>هل حصلت على برامج تدريبية سابقة في مركز تدريب التنمية المحلية بسقارة؟ <span class="es-req">*</span></legend>
                                    <div class="es-choices">
                                        <div class="es-choice">
                                            <input type="radio" name="has_taken_previous_courses" id="prev_yes" value="1" @checked(old('has_taken_previous_courses') === '1') required>
                                            <label for="prev_yes">نعم</label>
                                        </div>
                                        <div class="es-choice">
                                            <input type="radio" name="has_taken_previous_courses" id="prev_no" value="0" @checked(old('has_taken_previous_courses') === '0') required>
                                            <label for="prev_no">لا</label>
                                        </div>
                                    </div>
                                    @error('has_taken_previous_courses')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </fieldset>
                                <div class="mt-3" id="previousCoursesNamesWrapper" style="display:{{ old('has_taken_previous_courses') === '1' ? 'block' : 'none' }};">
                                    <label for="previous_courses_names" class="form-label">في حالة الإجابة بنعم، يرجى ذكر أسماء البرامج التدريبية</label>
                                    <textarea name="previous_courses_names" id="previous_courses_names" class="form-control{{ $fieldError('previous_courses_names') }}" rows="3">{{ old('previous_courses_names') }}</textarea>
                                    @error('previous_courses_names')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </section>

                            <div class="es-card es-submit">
                                <p>بإرسال الطلب تُقر بصحة البيانات والمرفقات. يُراجع الطلب من إدارة المركز وتصلك النتيجة إلكترونياً.</p>
                                <button type="submit" class="es-btn es-btn--lg"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> إرسال الطلب</button>
                            </div>
                        </form>
                    </div>

                    <aside class="es-aside" aria-label="ملخص البرنامج">
                        <div class="es-card es-summary">
                            <div class="es-summary__media">
                                <img src="{{ $program->imageUrl() }}" alt="" onerror="this.onerror=null;this.src='{{ asset('images/bg/estidama.jpeg') }}'">
                            </div>
                            <div class="es-summary__head">
                                <strong>{{ $program->title }}</strong>
                                <span>{{ $program->trainingCenter->name ?? 'مركز استدامة' }}</span>
                            </div>
                            <dl class="es-rows">
                                <div class="es-row"><dt><i class="fa-solid fa-circle-dot" aria-hidden="true"></i> الحالة</dt><dd>{{ $program->statusLabel() }}</dd></div>
                                @if ($program->start_date)
                                    <div class="es-row"><dt><i class="fa-regular fa-calendar" aria-hidden="true"></i> تاريخ البدء</dt><dd>{{ $program->start_date->format('Y-m-d') }}</dd></div>
                                @endif
                                @if ($program->end_date)
                                    <div class="es-row"><dt><i class="fa-regular fa-calendar-check" aria-hidden="true"></i> تاريخ الانتهاء</dt><dd>{{ $program->end_date->format('Y-m-d') }}</dd></div>
                                @endif
                            </dl>
                            <div class="es-price">
                                <div class="es-price__label">رسوم البرنامج<small>{{ $program->isFree() ? 'لا توجد رسوم' : 'تُسدد إلكترونياً بعد قبول الطلب' }}</small></div>
                                <div class="es-price__amount">{{ $program->priceLabel() }}</div>
                            </div>
                        </div>

                        <div class="es-card es-mini">
                            <h3><i class="fa-solid fa-list-check" aria-hidden="true"></i> جهّز قبل البدء</h3>
                            <ul>
                                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>صورة واضحة لوجهي بطاقة الرقم القومي.</span></li>
                                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>بيانات المؤهل الدراسي وجهة العمل.</span></li>
                                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>بيان حالة بصيغة PDF (اختياري).</span></li>
                            </ul>
                        </div>
                    </aside>
                </div>
            @endif
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        // Show/hide the "previous courses" textarea based on radio button selection
        document.querySelectorAll('input[name="has_taken_previous_courses"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('previousCoursesNamesWrapper').style.display = this.value === '1' ?
                    'block' : 'none';
            });
        });

        // Highlight the form section currently on screen in the stepper
        (() => {
            const items = [...document.querySelectorAll('.es-stepper li')];
            if (!items.length || !('IntersectionObserver' in window)) return;
            const io = new IntersectionObserver(entries => {
                entries.forEach(en => {
                    if (!en.isIntersecting) return;
                    items.forEach(li => li.classList.toggle('is-active', li.querySelector('a').getAttribute('href') === '#' + en.target.id));
                });
            }, { rootMargin: '-40% 0px -55% 0px' });
            document.querySelectorAll('.es-form[id]').forEach(s => io.observe(s));
        })();
    </script>
@endpush
