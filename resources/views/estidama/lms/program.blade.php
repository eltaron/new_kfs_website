@extends('layouts.app')

@section('title', $program->title . ' — استدامة')
@section('description', \Illuminate\Support\Str::limit(strip_tags($program->description), 155))
@include('estidama.lms.partials.learning-assets')

@php
    $duration = $totalMinutes ? ($totalMinutes >= 60 ? round($totalMinutes / 60, 1) . ' ساعة' : $totalMinutes . ' دقيقة') : null;
@endphp

@section('content')
<div class="es-page">
    <x-kfs.page-hero size="detail" :title="$program->title" :kicker="$program->trainingCenter->name ?? 'مركز استدامة'" icon="fa-solid fa-graduation-cap"
        :crumbs="['استدامة' => route('estidama.index'), 'البرامج التدريبية' => route('estidama.programs')]">
        <x-slot:meta>
            <span><i class="fa-solid fa-circle-dot" aria-hidden="true"></i>{{ $program->statusLabel() }}</span>
            <span><i class="fa-solid fa-layer-group" aria-hidden="true"></i>{{ $modules->count() }} وحدات · {{ $lessonsCount }} درسًا</span>
            @if ($duration)
                <span><i class="fa-regular fa-clock" aria-hidden="true"></i>{{ $duration }}</span>
            @endif
            @if ($program->levelLabel())
                <span><i class="fa-solid fa-signal" aria-hidden="true"></i>{{ $program->levelLabel() }}</span>
            @endif
            @if ($program->instructor)
                <span><i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i>{{ $program->instructor->name }}</span>
            @endif
            <span><i class="fa-solid fa-tag" aria-hidden="true"></i>{{ $program->priceLabel() }}</span>
            @if ($program->certificate_enabled)
                <span><i class="fa-solid fa-award" aria-hidden="true"></i>شهادة إلكترونية معتمدة</span>
            @endif
        </x-slot:meta>
        <x-slot:actions>
            <a href="#es-enroll" class="btn-kfs-gold"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> الالتحاق بالبرنامج</a>
        </x-slot:actions>
    </x-kfs.page-hero>

    <div class="container">
        <div class="es-layout">
            <div>
                @include('estidama.lms.partials.flash')

                <section class="es-card es-pad" aria-labelledby="esAbout">
                    <h2 class="es-card__title" id="esAbout"><span class="es-ico" aria-hidden="true"><i class="fa-solid fa-circle-info"></i></span> عن البرنامج</h2>
                    <div class="es-prose">{!! clean($program->description) !!}</div>
                </section>

                <section class="es-card es-pad" aria-labelledby="esSyllabus">
                    <h2 class="es-card__title" id="esSyllabus">
                        <span class="es-ico" aria-hidden="true"><i class="fa-solid fa-list-check"></i></span> محتوى البرنامج
                        @if ($lessonsCount)<small>{{ $modules->count() }} وحدات · {{ $lessonsCount }} درسًا</small>@endif
                    </h2>
                    @if ($modules->isEmpty())
                        <p class="es-empty"><i class="fa-regular fa-folder-open" aria-hidden="true"></i>سيُعلن عن محتوى البرنامج قريبًا.</p>
                    @else
                        <div class="es-syllabus">
                            @foreach ($modules as $module)
                                <details @if ($loop->first) open @endif>
                                    <summary>
                                        <span class="es-num">{{ $loop->iteration }}</span>
                                        <span class="es-grow">{{ $module->title }}</span>
                                        <span class="es-badge">{{ $module->lessons->count() }} دروس</span>
                                    </summary>
                                    @if ($module->description)
                                        <p class="es-syllabus__desc">{{ $module->description }}</p>
                                    @endif
                                    <ul>
                                        @foreach ($module->lessons as $lesson)
                                            <li>
                                                <i class="fa-solid {{ $lesson->icon() }}" aria-hidden="true"></i>
                                                <span class="es-grow">{{ $lesson->title }} <span class="visually-hidden">({{ $lesson->typeLabel() }})</span></span>
                                                @if ($lesson->is_preview && $lesson->embedUrl())
                                                    <a class="es-badge es-badge--info" href="{{ $lesson->video_url }}" target="_blank" rel="noopener">معاينة مجانية</a>
                                                @endif
                                                @if ($lesson->estimated_minutes)
                                                    <span class="es-dur">{{ $lesson->estimated_minutes }} د</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            <aside class="es-aside" aria-label="تفاصيل البرنامج والالتحاق">
                <div class="es-card es-summary" id="es-enroll">
                    <div class="es-summary__media">
                        <img src="{{ $program->imageUrl() }}" alt="" onerror="this.onerror=null;this.src='{{ asset('images/bg/estidama.jpeg') }}'">
                    </div>
                    <div class="es-price">
                        <div class="es-price__label">رسوم البرنامج<small>{{ $program->requires_application ? 'طلب التحاق ومراجعة' : 'تسجيل مباشر' }}</small></div>
                        <div class="es-price__amount">{{ $program->priceLabel() }}</div>
                    </div>

                    {{-- Call to action: the right step for this learner --}}
                    <div class="es-cta">
                        @guest
                            <a href="{{ route('login') }}" class="es-btn es-btn--block"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> سجّل الدخول للالتحاق</a>
                        @else
                            @if ($enrollment && $enrollment->status === 'pending')
                                <span class="es-cta__state es-cta__state--warn"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> تسجيلك قيد المراجعة</span>
                            @elseif ($enrollment && $enrollment->needsPayment())
                                <a href="{{ route('payments.pay', ['type' => $enrollment->getMorphClass(), 'id' => $enrollment->id]) }}" class="es-btn es-btn--block">
                                    <i class="fa-solid fa-credit-card" aria-hidden="true"></i> سداد رسوم البرنامج ({{ $program->priceLabel() }})
                                </a>
                                <p class="es-cta__note">يتم السداد إلكترونيًا عبر بوابة الدفع الحكومية.</p>
                            @elseif ($enrollment)
                                <a href="{{ route('lms.course', $program) }}" class="es-btn es-btn--block">
                                    <i class="fa-solid fa-circle-play" aria-hidden="true"></i> {{ $enrollment->progress_percent > 0 ? 'متابعة التعلم' : 'ابدأ التعلم' }}
                                </a>
                                @include('estidama.lms.partials.progress', ['percent' => $enrollment->progress_percent, 'label' => 'تقدمك'])
                                @if ($enrollment->certificate)
                                    <a href="{{ route('lms.certificates.show', $enrollment->certificate->serial) }}" class="es-btn es-btn--ghost es-btn--block"><i class="fa-solid fa-award" aria-hidden="true"></i> عرض الشهادة</a>
                                @endif
                            @elseif ($application && $application->status === 'pending')
                                <span class="es-cta__state es-cta__state--warn"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> طلب الالتحاق قيد المراجعة</span>
                            @elseif ($application && $application->status === 'rejected')
                                <span class="es-cta__state es-cta__state--bad"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> لم يُقبل طلبك</span>
                                @if ($application->rejection_reason)
                                    <p class="es-cta__note">السبب: {{ $application->rejection_reason }}</p>
                                @endif
                            @elseif ($program->status === 'closed')
                                <span class="es-cta__state es-cta__state--muted"><i class="fa-solid fa-lock" aria-hidden="true"></i> التسجيل مغلق</span>
                            @elseif ($program->requires_application)
                                <a href="{{ route('estidama.apply', $program) }}" class="es-btn es-btn--block"><i class="fa-solid fa-file-pen" aria-hidden="true"></i> تقديم طلب الالتحاق</a>
                                <p class="es-cta__note">يُراجع الطلب من إدارة المركز ثم يُفتح لك المحتوى{{ $program->isFree() ? '' : ' بعد سداد الرسوم' }}.</p>
                            @else
                                <form method="POST" action="{{ route('lms.enroll', $program) }}">
                                    @csrf
                                    <button type="submit" class="es-btn es-btn--block">
                                        <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                                        {{ $program->isFree() ? 'التسجيل الآن مجانًا' : 'التسجيل والسداد (' . $program->priceLabel() . ')' }}
                                    </button>
                                </form>
                            @endif
                        @endguest
                    </div>
                </div>

                <div class="es-card es-mini">
                    <h3><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> تفاصيل</h3>
                    <dl class="es-rows">
                        <div class="es-row"><dt>تاريخ البدء</dt><dd>{{ optional($program->start_date)->format('Y-m-d') ?? '—' }}</dd></div>
                        <div class="es-row"><dt>تاريخ الانتهاء</dt><dd>{{ optional($program->end_date)->format('Y-m-d') ?? '—' }}</dd></div>
                        <div class="es-row"><dt>الرسوم</dt><dd>{{ $program->priceLabel() }}</dd></div>
                        <div class="es-row"><dt>طريقة الالتحاق</dt><dd>{{ $program->requires_application ? 'طلب التحاق ومراجعة' : 'تسجيل مباشر' }}</dd></div>
                        <div class="es-row"><dt>نظام الدروس</dt><dd>{{ $program->sequential ? 'بالترتيب (يُفتح الدرس بعد إتمام السابق)' : 'حر' }}</dd></div>
                        @if ($program->finalExam)
                            <div class="es-row"><dt>الامتحان النهائي</dt><dd>{{ $program->finalExam->duration_minutes }} دقيقة · النجاح {{ $program->finalExam->passing_score }}%</dd></div>
                        @endif
                        <div class="es-row"><dt>الشهادة</dt><dd>{{ $program->certificate_enabled ? 'إلكترونية برمز تحقق QR' : 'لا يوجد' }}</dd></div>
                    </dl>
                </div>

                <a href="{{ route('lms.certificates.verify') }}" class="es-link"><i class="fa-solid fa-qrcode" aria-hidden="true"></i> التحقق من شهادة صادرة</a>
            </aside>
        </div>
    </div>
</div>
@endsection
