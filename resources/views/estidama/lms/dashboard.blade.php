@extends('layouts.app')

@section('title', 'تعلّمي — استدامة')
@include('estidama.lms.partials.learning-assets')

@section('content')
<div class="es-page">
    <x-kfs.page-hero size="compact" kicker="مساحة التعلم الخاصة بك" icon="fa-solid fa-book-open-reader" current="تعلّمي"
        title="أهلاً {{ \Illuminate\Support\Str::of($user->name)->explode(' ')->first() }}، واصل رحلة التعلم"
        subtitle="تابع برامجك التدريبية، واستأنف من حيث توقفت، واحصل على شهاداتك الإلكترونية الموثّقة."
        :crumbs="['استدامة' => route('estidama.index')]">
        <x-slot:actions>
            <a href="{{ route('estidama.programs') }}" class="btn-kfs-gold"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> استكشف البرامج</a>
            <a href="{{ route('lms.certificates.verify') }}" class="btn-kfs-outline"><i class="fa-solid fa-qrcode" aria-hidden="true"></i> التحقق من شهادة</a>
        </x-slot:actions>
    </x-kfs.page-hero>

    <div class="container">
        <div class="es-stats es-statsbar">
            <div class="es-card es-stat">
                <span class="es-ico" aria-hidden="true"><i class="fa-solid fa-book-open-reader"></i></span>
                <div><b>{{ $active->count() }}</b><span>برامج قيد التعلم</span></div>
            </div>
            <div class="es-card es-stat">
                <span class="es-ico es-ico--ok" aria-hidden="true"><i class="fa-solid fa-flag-checkered"></i></span>
                <div><b>{{ $completed->count() }}</b><span>برامج مكتملة</span></div>
            </div>
            <div class="es-card es-stat">
                <span class="es-ico es-ico--gold" aria-hidden="true"><i class="fa-solid fa-award"></i></span>
                <div><b>{{ $certificates->count() }}</b><span>شهادات</span></div>
            </div>
            <div class="es-card es-stat">
                <span class="es-ico" aria-hidden="true"><i class="fa-solid fa-medal"></i></span>
                <div><b>{{ $badges->count() }}</b><span>شارات</span></div>
            </div>
        </div>

        <div class="es-layout">
            <div>
                @include('estidama.lms.partials.flash')

                <section class="es-card es-pad" aria-labelledby="esActive">
                    <h2 class="es-card__title" id="esActive"><span class="es-ico" aria-hidden="true"><i class="fa-solid fa-book-open-reader"></i></span> برامجي الحالية</h2>
                    @forelse ($active as $enrollment)
                        @php($program = $enrollment->trainingProgram)
                        <div class="es-course">
                            <img src="{{ $program->imageUrl() }}" alt="" onerror="this.onerror=null;this.src='{{ asset('images/bg/estidama.jpeg') }}'">
                            <div>
                                <h3><a href="{{ route('estidama.program.show', $program) }}">{{ $program->title }}</a></h3>
                                <div class="es-course__meta">
                                    {{ $program->trainingCenter->name ?? '' }}
                                    @if ($enrollment->lastLesson) · آخر درس: {{ $enrollment->lastLesson->title }} @endif
                                </div>
                                @include('estidama.lms.partials.progress', ['percent' => $enrollment->progress_percent, 'label' => 'التقدم'])
                            </div>
                            <div class="es-course__actions">
                                @if ($enrollment->needsPayment())
                                    <a href="{{ route('payments.pay', ['type' => $enrollment->getMorphClass(), 'id' => $enrollment->id]) }}" class="es-btn es-btn--sm"><i class="fa-solid fa-credit-card" aria-hidden="true"></i> سداد {{ $program->priceLabel() }}</a>
                                @else
                                    <a href="{{ route('lms.course', $program) }}" class="es-btn es-btn--sm"><i class="fa-solid fa-play" aria-hidden="true"></i> {{ $enrollment->progress_percent > 0 ? 'استئناف' : 'ابدأ' }}</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="es-empty"><i class="fa-regular fa-compass" aria-hidden="true"></i><span>لا توجد برامج قيد التعلم حاليًا. <a href="{{ route('estidama.programs') }}">تصفّح البرامج المتاحة</a>.</span></div>
                    @endforelse
                </section>

                @if ($pending->isNotEmpty() || $applications->isNotEmpty())
                    <section class="es-card es-pad" aria-labelledby="esPending">
                        <h2 class="es-card__title" id="esPending"><span class="es-ico es-ico--gold" aria-hidden="true"><i class="fa-solid fa-hourglass-half"></i></span> طلبات قيد المراجعة</h2>
                        <div class="es-table-wrap">
                            <table class="es-table">
                                <thead><tr><th scope="col">البرنامج</th><th scope="col">الحالة</th><th scope="col">ملاحظات</th></tr></thead>
                                <tbody>
                                    @foreach ($pending as $enrollment)
                                        <tr><td>{{ $enrollment->trainingProgram->title }}</td><td><span class="es-badge es-badge--warn">تسجيل قيد المراجعة</span></td><td>—</td></tr>
                                    @endforeach
                                    @foreach ($applications as $application)
                                        <tr>
                                            <td>{{ $application->trainingProgram->title ?? '—' }}</td>
                                            <td><span class="es-badge {{ $application->status === 'rejected' ? 'es-badge--bad' : 'es-badge--warn' }}">{{ \Modules\Estidama\Models\TrainingApplication::STATUSES[$application->status] ?? $application->status }}</span></td>
                                            <td>{{ $application->rejection_reason ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                <section class="es-card es-pad" aria-labelledby="esDone">
                    <h2 class="es-card__title" id="esDone"><span class="es-ico es-ico--ok" aria-hidden="true"><i class="fa-solid fa-flag-checkered"></i></span> برامج مكتملة</h2>
                    @forelse ($completed as $enrollment)
                        <div class="es-course">
                            <img src="{{ $enrollment->trainingProgram->imageUrl() }}" alt="" onerror="this.onerror=null;this.src='{{ asset('images/bg/estidama.jpeg') }}'">
                            <div>
                                <h3>{{ $enrollment->trainingProgram->title }}</h3>
                                <div class="es-course__meta"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i> أُكمل في {{ optional($enrollment->completed_at)->format('Y-m-d') ?? '—' }}</div>
                            </div>
                            <div class="es-course__actions">
                                @if ($enrollment->certificate)
                                    <a href="{{ route('lms.certificates.show', $enrollment->certificate->serial) }}" class="es-btn es-btn--sm"><i class="fa-solid fa-award" aria-hidden="true"></i> الشهادة</a>
                                @elseif ($enrollment->certificate_path)
                                    <a href="{{ route('lms.enrollment.certificate-file', $enrollment) }}" class="es-btn es-btn--soft es-btn--sm"><i class="fa-solid fa-paperclip" aria-hidden="true"></i> الشهادة المرفقة</a>
                                @endif
                                <a href="{{ route('lms.course', $enrollment->trainingProgram) }}" class="es-btn es-btn--ghost es-btn--sm">مراجعة المحتوى</a>
                            </div>
                        </div>
                    @empty
                        <div class="es-empty"><i class="fa-solid fa-medal" aria-hidden="true"></i><span>أكمل برنامجك الأول لتحصل على شهادة إلكترونية.</span></div>
                    @endforelse
                </section>
            </div>

            <aside class="es-aside" aria-label="شهاداتي وشاراتي واختباراتي">
                <section class="es-card es-mini" aria-labelledby="esCerts">
                    <h3 id="esCerts"><i class="fa-solid fa-award" aria-hidden="true"></i> شهاداتي</h3>
                    @forelse ($certificates as $certificate)
                        <div class="es-list-item">
                            <div>
                                <strong>{{ $certificate->program_title }}</strong>
                                <code>{{ $certificate->serial }}</code>
                            </div>
                            <a href="{{ route('lms.certificates.show', $certificate->serial) }}" class="es-btn es-btn--ghost es-btn--icon" aria-label="عرض شهادة {{ $certificate->program_title }}"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                        </div>
                    @empty
                        <div class="es-empty"><i class="fa-regular fa-file" aria-hidden="true"></i><span>لا توجد شهادات بعد.</span></div>
                    @endforelse
                </section>

                <section class="es-card es-mini" aria-labelledby="esBadges">
                    <h3 id="esBadges"><i class="fa-solid fa-medal" aria-hidden="true"></i> شاراتي</h3>
                    @if ($badges->isEmpty())
                        <div class="es-empty"><i class="fa-regular fa-star" aria-hidden="true"></i><span>اجمع الشارات بإتمام الدروس والبرامج والتفوق في الامتحانات.</span></div>
                    @else
                        <div class="es-badges">
                            @foreach ($badges as $badge)
                                <div class="es-badge-item" title="{{ $badge->description }}">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($badge->image) }}" alt="">
                                    <b>{{ $badge->name }}</b>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="es-card es-mini" aria-labelledby="esAttempts">
                    <h3 id="esAttempts"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> آخر الاختبارات</h3>
                    @forelse ($attempts as $attempt)
                        <div class="es-list-item">
                            <span>{{ \Illuminate\Support\Str::limit($attempt->exam->title ?? '—', 30) }}</span>
                            @if ($attempt->isInProgress())
                                <a href="{{ route('lms.attempts.show', $attempt) }}" class="es-badge es-badge--info">متابعة الحل</a>
                            @elseif ($attempt->status === 'needs_grading')
                                <span class="es-badge es-badge--warn">بانتظار التصحيح</span>
                            @else
                                <a href="{{ route('lms.attempts.review', $attempt) }}" class="es-badge {{ $attempt->passed ? 'es-badge--ok' : 'es-badge--bad' }}">{{ $attempt->score }}%</a>
                            @endif
                        </div>
                    @empty
                        <div class="es-empty"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i><span>لم تؤدِّ أي اختبار بعد.</span></div>
                    @endforelse
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection
