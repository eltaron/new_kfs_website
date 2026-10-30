@extends('layouts.app')

@section('title', $exam->title . ' — استدامة')
@include('estidama.lms.partials.learning-assets')

@section('content')
<div class="es-page">
    <x-kfs.page-hero size="compact" :title="$exam->title" :kicker="$exam->kindLabel()" icon="fa-solid fa-clipboard-question" :current="$exam->kindLabel()"
        :crumbs="['تعلّمي' => route('lms.dashboard'), \Illuminate\Support\Str::limit($program->title, 40) => route('lms.course', $program)]">
        <x-slot:meta>
            <span><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> {{ $program->title }}</span>
        </x-slot:meta>
    </x-kfs.page-hero>

    <div class="container">
        <div class="es-layout">
            <div>
                @include('estidama.lms.partials.flash')
                <section class="es-card es-pad" aria-labelledby="esRules">
                    <h2 class="es-card__title" id="esRules"><span class="es-ico" aria-hidden="true"><i class="fa-solid fa-scroll"></i></span> تعليمات الامتحان</h2>
                    @if ($exam->description)
                        <p>{{ $exam->description }}</p>
                    @endif

                    <div class="es-exam-facts">
                        <div><i class="fa-solid fa-list-ol" aria-hidden="true"></i><b>{{ $questionsCount }}</b><small>سؤالاً</small></div>
                        <div><i class="fa-regular fa-clock" aria-hidden="true"></i><b>{{ $exam->duration_minutes }}</b><small>دقيقة</small></div>
                        <div><i class="fa-solid fa-bullseye" aria-hidden="true"></i><b>{{ $exam->passing_score }}%</b><small>درجة النجاح</small></div>
                        <div><i class="fa-solid fa-rotate" aria-hidden="true"></i><b>{{ $remaining !== null ? $remaining : '∞' }}</b><small>محاولات متبقية</small></div>
                    </div>

                    <ul class="es-rules">
                        <li><i class="fa-regular fa-clock" aria-hidden="true"></i><span>مدة الامتحان: <b>{{ $exam->duration_minutes }} دقيقة</b> — يبدأ العدّ عند الضغط على «ابدأ» ويُسلَّم تلقائيًا عند انتهاء الوقت.</span></li>
                        <li><i class="fa-solid fa-rotate" aria-hidden="true"></i><span>المحاولات المسموح بها: <b>{{ $exam->attemptsLabel() }}</b>@if ($remaining !== null) — المتبقي لك: <b>{{ $remaining }}</b>@endif</span></li>
                        @if ($exam->shuffle_questions)<li><i class="fa-solid fa-shuffle" aria-hidden="true"></i><span>تظهر الأسئلة بترتيب عشوائي في كل محاولة.</span></li>@endif
                        <li><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span>تُحفظ إجاباتك تلقائيًا أثناء الحل، فلا تقلق إذا انقطع الاتصال لحظيًا.</span></li>
                    </ul>

                    <div class="es-exam-start">
                        @if ($open)
                            <a href="{{ route('lms.attempts.show', $open) }}" class="es-btn es-btn--lg"><i class="fa-solid fa-pen" aria-hidden="true"></i> متابعة المحاولة الجارية</a>
                        @elseif ($blocker)
                            <div class="es-alert es-alert--info mb-0 w-100"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div>{{ $blocker }}</div></div>
                        @else
                            <form method="POST" action="{{ route('lms.exams.start', [$program, $exam]) }}" onsubmit="return confirm('سيبدأ المؤقت الآن. هل أنت مستعد؟');">
                                @csrf
                                @if ($lesson)<input type="hidden" name="lesson" value="{{ $lesson->id }}">@endif
                                <button type="submit" class="es-btn es-btn--lg"><i class="fa-solid fa-play" aria-hidden="true"></i> ابدأ الامتحان الآن</button>
                                <a href="{{ route('lms.course', $program) }}" class="es-btn es-btn--ghost es-btn--lg">عودة للبرنامج</a>
                            </form>
                        @endif
                    </div>
                </section>
            </div>

            <aside class="es-aside" aria-label="محاولاتك السابقة">
                <section class="es-card es-mini" aria-labelledby="esPrev">
                    <h3 id="esPrev"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> محاولاتك السابقة</h3>
                    @if ($attempts->isEmpty())
                        <div class="es-empty"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i><span>لا توجد محاولات بعد.</span></div>
                    @else
                        <div class="es-table-wrap">
                            <table class="es-table">
                                <thead><tr><th scope="col">#</th><th scope="col">الحالة</th><th scope="col">الدرجة</th><th scope="col"><span class="visually-hidden">إجراء</span></th></tr></thead>
                                <tbody>
                                @foreach ($attempts as $attempt)
                                    <tr>
                                        <td>{{ $attempt->attempt_number }}</td>
                                        <td>{{ $attempt->statusLabel() }}</td>
                                        <td>
                                            @if ($attempt->status === 'submitted')
                                                <span class="es-badge {{ $attempt->passed ? 'es-badge--ok' : 'es-badge--bad' }}">{{ $attempt->score }}%</span>
                                            @else — @endif
                                        </td>
                                        <td>@if ($attempt->isFinished())<a href="{{ route('lms.attempts.review', $attempt) }}" class="es-link">مراجعة</a>@endif</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection
