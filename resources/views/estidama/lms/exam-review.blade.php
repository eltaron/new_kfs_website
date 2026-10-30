@extends('layouts.app')

@section('title', 'نتيجة: ' . $exam->title)
@include('estidama.lms.partials.learning-assets')

@section('content')
@php($answers = $attempt->answers ?? [])
@php($manual = $answers['_manual'] ?? [])
@php($pending = $attempt->status === 'needs_grading')
@php($scoreClass = $pending ? 'es-score--pending' : ($attempt->passed ? 'es-score--pass' : 'es-score--fail'))
<div class="es-page">
    <x-kfs.page-hero size="compact" :title="$exam->title" kicker="نتيجة الاختبار" icon="fa-solid fa-square-poll-vertical" current="نتيجة الاختبار"
        :crumbs="$program ? ['تعلّمي' => route('lms.dashboard'), \Illuminate\Support\Str::limit($program->title, 40) => route('lms.course', $program)] : ['تعلّمي' => route('lms.dashboard')]">
        <x-slot:meta>
            <span><i class="fa-solid fa-rotate" aria-hidden="true"></i> المحاولة {{ $attempt->attempt_number }}</span>
            <span><i class="fa-solid fa-clipboard-question" aria-hidden="true"></i> {{ $exam->kindLabel() }}</span>
        </x-slot:meta>
    </x-kfs.page-hero>

    <div class="container es-wrap-900">
        <div class="es-tight">
            @include('estidama.lms.partials.flash')

            <section class="es-card es-pad es-result" aria-labelledby="esResultTitle">
                <div class="es-score {{ $scoreClass }}" style="--p: {{ $pending ? 0 : (int) $attempt->score }}">
                    <span>{{ $pending ? '…' : $attempt->score . '%' }}</span>
                </div>
                @if ($pending)
                    <h2 id="esResultTitle">بانتظار تصحيح المدرب</h2>
                    <p>تحتوي إجاباتك على أسئلة مقالية، وستظهر نتيجتك النهائية بعد تصحيحها.</p>
                @elseif ($attempt->passed)
                    <h2 id="esResultTitle" class="is-pass">ناجح — أحسنت!</h2>
                    <p>درجة النجاح المطلوبة {{ $exam->passing_score }}%.</p>
                @else
                    <h2 id="esResultTitle" class="is-fail">لم تبلغ درجة النجاح</h2>
                    <p>درجة النجاح المطلوبة {{ $exam->passing_score }}%. راجع الدروس ثم حاول مجددًا.</p>
                @endif
                @if ($attempt->feedback)
                    <div class="es-explain-note"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><span>ملاحظات المدرب: {{ $attempt->feedback }}</span></div>
                @endif

                <div class="es-actions">
                    @if ($enrollment && $enrollment->certificate)
                        <a href="{{ route('lms.certificates.show', $enrollment->certificate->serial) }}" class="es-btn es-btn--gold"><i class="fa-solid fa-award" aria-hidden="true"></i> عرض شهادتك</a>
                    @endif
                    @if (! $attempt->passed && ! $pending && ! $blocker && $program)
                        <a href="{{ route('lms.exams.show', [$program, $exam]) . ($attempt->program_lesson_id ? '?lesson=' . $attempt->program_lesson_id : '') }}" class="es-btn">
                            <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> محاولة جديدة @if ($remaining !== null)({{ $remaining }} متبقية)@endif
                        </a>
                    @endif
                    @if ($program)
                        <a href="{{ route('lms.course', $program) }}" class="es-btn es-btn--ghost">العودة للبرنامج</a>
                    @endif
                </div>
            </section>

            @if ($showAnswers && ! $pending)
                <h2 class="es-subhead">مراجعة الإجابات</h2>
                @foreach ($questions as $i => $question)
                    @php($given = $answers[$question->id] ?? null)
                    <div class="es-question">
                        <div class="es-question__text">
                            <span class="es-qnum">{{ $i + 1 }}</span>
                            <span class="es-grow">{!! clean($question->question_text) !!}</span>
                        </div>
                        @if ($question->isEssay())
                            <div class="es-answer">{{ $given ?: '— لم تُجب —' }}</div>
                            <span class="es-badge es-badge--info">الدرجة: {{ $manual[$question->id] ?? 0 }} / {{ $question->points }}</span>
                        @else
                            @foreach ($question->display_options as $option)
                                @php($isCorrect = $option === $question->correct_answer)
                                @php($isGiven = $option === $given)
                                <div class="es-option {{ $isCorrect ? 'correct' : ($isGiven ? 'wrong' : '') }}">
                                    <i class="fa-solid {{ $isCorrect ? 'fa-circle-check ico-ok' : ($isGiven ? 'fa-circle-xmark ico-bad' : 'fa-circle ico-none') }}" aria-hidden="true"></i>
                                    <span>{{ $option }}</span>
                                    @if ($isGiven)<span class="es-badge">إجابتك</span>@endif
                                    @if ($isCorrect)<span class="visually-hidden">(الإجابة الصحيحة)</span>@endif
                                </div>
                            @endforeach
                            @if ($given === null)
                                <span class="es-badge es-badge--warn">لم تُجب عن هذا السؤال</span>
                            @endif
                        @endif
                        @if ($question->explanation)
                            <div class="es-explain-note"><i class="fa-regular fa-lightbulb" aria-hidden="true"></i><span>{{ $question->explanation }}</span></div>
                        @endif
                    </div>
                @endforeach
            @elseif (! $showAnswers)
                <p class="es-empty mt-4"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i>مراجعة الإجابات الصحيحة غير متاحة لهذا الاختبار.</p>
            @endif
        </div>
    </div>
</div>
@endsection
