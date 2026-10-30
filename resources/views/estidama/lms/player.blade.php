@extends('layouts.app')

@section('title', $lesson->title . ' — ' . $program->title)
@include('estidama.lms.partials.learning-assets')

@section('content')
<div class="es-page">
    <x-kfs.page-hero size="detail" :title="$lesson->title" :kicker="$program->title" :icon="'fa-solid ' . $lesson->icon()"
        :crumbs="['تعلّمي' => route('lms.dashboard'), \Illuminate\Support\Str::limit($program->title, 50) => route('estidama.program.show', $program)]">
        <x-slot:meta>
            <span><i class="fa-solid {{ $lesson->icon() }}" aria-hidden="true"></i> {{ $lesson->typeLabel() }}</span>
            @if ($lesson->estimated_minutes)
                <span><i class="fa-regular fa-clock" aria-hidden="true"></i> {{ $lesson->estimated_minutes }} دقيقة</span>
            @endif
            <span><i class="fa-solid fa-chart-line" aria-hidden="true"></i> تقدمك {{ (int) $enrollment->progress_percent }}%</span>
            @if ($completed)
                <span class="es-hero-done"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> أتممت هذا الدرس</span>
            @endif
        </x-slot:meta>
    </x-kfs.page-hero>

    <div class="container-xl">
        <div class="es-player">
            {{-- ===== Main column ===== --}}
            <main class="es-player__main" id="lesson-content">
                @include('estidama.lms.partials.flash')

                @if ($lesson->summary)
                    <p class="es-lead">{{ $lesson->summary }}</p>
                @endif

                {{-- ----- Lesson body by type ----- --}}
                @switch($lesson->type)
                    @case('video')
                        @if ($lesson->embedUrl())
                            <div class="es-media">
                                <iframe src="{{ $lesson->embedUrl() }}" title="فيديو: {{ $lesson->title }}" loading="lazy"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                            </div>
                            <p class="es-hint"><i class="fa-solid fa-closed-captioning" aria-hidden="true"></i> يمكنك تفعيل الترجمة النصية من زر CC في مشغل الفيديو.</p>
                        @elseif ($lesson->isUploadedVideo())
                            <div class="es-media">
                                <video controls preload="metadata" playsinline
                                    data-lms-video data-resume="{{ (int) $progressRecord->last_position_seconds }}"
                                    aria-label="فيديو: {{ $lesson->title }}">
                                    <source src="{{ route('lms.lesson.media', [$lesson, 'file']) }}" type="video/mp4">
                                    @if ($lesson->captions_path)
                                        <track kind="captions" srclang="ar" label="العربية" src="{{ route('lms.lesson.media', [$lesson, 'captions']) }}" default>
                                    @endif
                                    متصفحك لا يدعم تشغيل الفيديو. <a href="{{ route('lms.lesson.media', [$lesson, 'file']) }}">تنزيل الفيديو</a>
                                </video>
                            </div>
                            @if ($progressRecord->last_position_seconds > 5 && ! $completed)
                                <p class="es-hint"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> سيُستأنف الفيديو من حيث توقفت ({{ gmdate($progressRecord->last_position_seconds >= 3600 ? 'H:i:s' : 'i:s', $progressRecord->last_position_seconds) }}).</p>
                            @endif
                        @else
                            <div class="es-alert es-alert--info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div>لم يُرفع فيديو هذا الدرس بعد.</div></div>
                        @endif
                        @if ($lesson->body)
                            <div class="es-card es-pad es-prose mt-3">{!! clean($lesson->body) !!}</div>
                        @endif
                        @break

                    @case('pdf')
                        @if ($lesson->file_path)
                            <iframe class="es-pdf" src="{{ route('lms.lesson.media', [$lesson, 'file']) }}#toolbar=1&view=FitH" title="ملف PDF: {{ $lesson->title }}"></iframe>
                            <p class="mt-3 mb-0"><a href="{{ route('lms.lesson.media', [$lesson, 'file']) }}" target="_blank" rel="noopener" class="es-btn es-btn--ghost es-btn--sm"><i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i> فتح الملف في نافذة جديدة</a></p>
                        @endif
                        @if ($lesson->body)
                            <div class="es-card es-pad es-prose mt-3">{!! clean($lesson->body) !!}</div>
                        @endif
                        @break

                    @case('link')
                        <div class="es-card es-pad">
                            <div class="es-prose">
                                @if ($lesson->body)
                                    {!! clean($lesson->body) !!}
                                @else
                                    <p>يتضمن هذا الدرس موردًا خارجيًا. اطّلع عليه ثم عُد لتحديده كمكتمل.</p>
                                @endif
                            </div>
                            <a href="{{ $lesson->external_url }}" target="_blank" rel="noopener noreferrer" class="es-btn mt-3">
                                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> فتح المورد الخارجي
                                <span class="visually-hidden">(يفتح في نافذة جديدة)</span>
                            </a>
                        </div>
                        @break

                    @case('quiz')
                        <div class="es-card es-pad">
                            @if ($quiz)
                                <h2 class="es-card__title"><span class="es-ico" aria-hidden="true"><i class="fa-solid fa-clipboard-question"></i></span> {{ $quiz['exam']->title }}</h2>
                                @if ($quiz['exam']->description)<p>{{ $quiz['exam']->description }}</p>@endif
                                <div class="es-quiz-facts">
                                    <div><small>المدة</small><b>{{ $quiz['exam']->duration_minutes }} دقيقة</b></div>
                                    <div><small>درجة النجاح</small><b>{{ $quiz['exam']->passing_score }}%</b></div>
                                    <div><small>المحاولات المتبقية</small><b>{{ $quiz['remaining'] === null ? 'غير محدودة' : $quiz['remaining'] }}</b></div>
                                </div>
                                @if ($quiz['open'])
                                    <a href="{{ route('lms.attempts.show', $quiz['open']) }}" class="es-btn"><i class="fa-solid fa-pen" aria-hidden="true"></i> متابعة المحاولة الجارية</a>
                                @elseif ($quiz['blocker'])
                                    <div class="es-alert es-alert--info mb-0"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div>{{ $quiz['blocker'] }}</div></div>
                                @else
                                    <form method="POST" action="{{ route('lms.exams.start', [$program, $quiz['exam']]) }}">
                                        @csrf
                                        <input type="hidden" name="lesson" value="{{ $lesson->id }}">
                                        <button class="es-btn" type="submit"><i class="fa-solid fa-play" aria-hidden="true"></i> ابدأ الاختبار</button>
                                    </form>
                                @endif
                                @if ($quiz['attempts']->isNotEmpty())
                                    <div class="es-table-wrap mt-4">
                                        <table class="es-table">
                                            <thead><tr><th scope="col">المحاولة</th><th scope="col">الحالة</th><th scope="col">الدرجة</th><th scope="col"><span class="visually-hidden">إجراء</span></th></tr></thead>
                                            <tbody>
                                            @foreach ($quiz['attempts'] as $attempt)
                                                <tr>
                                                    <td>{{ $attempt->attempt_number }}</td>
                                                    <td>{{ $attempt->statusLabel() }}</td>
                                                    <td>{{ $attempt->isFinished() ? $attempt->score . '%' : '—' }}</td>
                                                    <td>@if ($attempt->isFinished())<a href="{{ route('lms.attempts.review', $attempt) }}" class="es-link">مراجعة</a>@endif</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            @else
                                <p class="mb-0">لم يُربط اختبار بهذا الدرس بعد.</p>
                            @endif
                        </div>
                        @break

                    @default
                        <article class="es-card es-pad es-prose">{!! clean($lesson->body) !!}</article>
                @endswitch

                {{-- ----- Actions ----- --}}
                <div class="es-card es-lesson-nav">
                    <div>
                        @if ($previous)
                            <a href="{{ route('lms.lesson', [$program, $previous]) }}" class="es-btn es-btn--ghost"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> الدرس السابق</a>
                        @endif
                    </div>
                    <div class="es-lesson-nav__end">
                        @if (! $completed && $lesson->type !== 'quiz')
                            <form method="POST" action="{{ route('lms.lesson.complete', [$program, $lesson]) }}">
                                @csrf
                                <button class="es-btn es-btn--soft" type="submit"><i class="fa-solid fa-check" aria-hidden="true"></i> تحديد كمكتمل</button>
                            </form>
                        @endif
                        @if ($next)
                            @if ($nextLocked)
                                <span class="es-btn es-btn--ghost is-disabled" aria-disabled="true" title="أكمل هذا الدرس أولاً"><i class="fa-solid fa-lock" aria-hidden="true"></i> الدرس التالي</span>
                            @else
                                <a href="{{ route('lms.lesson', [$program, $next]) }}" class="es-btn">الدرس التالي <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- ----- Personal notes ----- --}}
                <div class="es-card es-pad es-notes">
                    <label for="lesson-notes" class="es-card__title"><span class="es-ico es-ico--gold" aria-hidden="true"><i class="fa-regular fa-note-sticky"></i></span> ملاحظاتي على هذا الدرس</label>
                    <textarea id="lesson-notes" maxlength="5000" data-lms-notes
                        data-url="{{ route('lms.lesson.progress', [$program, $lesson]) }}"
                        placeholder="اكتب ملاحظاتك الخاصة… تُحفظ تلقائيًا ولا يراها غيرك.">{{ $progressRecord->notes }}</textarea>
                    <div class="status" aria-live="polite" data-lms-notes-status></div>
                </div>
            </main>

            {{-- ===== Sidebar: outline ===== --}}
            <aside class="es-card es-outline" aria-label="محتوى البرنامج"
                data-lms-progress-url="{{ route('lms.lesson.progress', [$program, $lesson]) }}">
                <div class="es-outline__head">
                    <h2>{{ $program->title }}</h2>
                    @include('estidama.lms.partials.progress', ['percent' => $enrollment->progress_percent, 'label' => 'تقدمك في البرنامج'])
                </div>
                <div class="es-outline__body">
                    @foreach ($outline as $section)
                        <div class="es-sec-title">
                            <span>{{ $section['module']->title }}</span>
                            <span>{{ collect($section['lessons'])->where('completed', true)->count() }}/{{ count($section['lessons']) }}</span>
                        </div>
                        @foreach ($section['lessons'] as $item)
                            @php($l = $item['lesson'])
                            @if ($item['locked'])
                                <span class="es-lesson-link locked" title="{{ $item['reason'] }}">
                                    <span class="state"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                                    <span>{{ $l->title }}<small>{{ $item['reason'] }}</small></span>
                                </span>
                            @else
                                <a href="{{ route('lms.lesson', [$program, $l]) }}" class="es-lesson-link {{ $l->id === $lesson->id ? 'active' : '' }}" @if ($l->id === $lesson->id) aria-current="page" @endif>
                                    <span class="state">
                                        @if ($item['completed'])
                                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i><span class="visually-hidden">مكتمل</span>
                                        @elseif ($l->id === $lesson->id)
                                            <i class="fa-solid fa-circle-play" aria-hidden="true"></i>
                                        @else
                                            <i class="fa-regular fa-circle" aria-hidden="true"></i>
                                        @endif
                                    </span>
                                    <span>{{ $l->title }}<small><i class="fa-solid {{ $l->icon() }}" aria-hidden="true"></i> {{ $l->typeLabel() }}@if ($l->estimated_minutes) · {{ $l->estimated_minutes }} د @endif</small></span>
                                </a>
                            @endif
                        @endforeach
                    @endforeach

                    @if ($finalExam)
                        <div class="es-final-box">
                            <strong><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> الامتحان النهائي</strong>
                            @if ($finalExamReady)
                                <a href="{{ route('lms.exams.show', [$program, $finalExam]) }}" class="es-btn es-btn--block es-btn--sm">الذهاب للامتحان</a>
                            @else
                                <p>يُفتح بعد إتمام جميع الدروس المطلوبة.</p>
                            @endif
                        </div>
                    @endif
                    @if ($certificate)
                        <div class="es-final-box">
                            <strong><i class="fa-solid fa-award" aria-hidden="true"></i> مبروك! صدرت شهادتك</strong>
                            <a href="{{ route('lms.certificates.show', $certificate->serial) }}" class="es-btn es-btn--gold es-btn--block es-btn--sm">عرض الشهادة</a>
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
