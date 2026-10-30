@extends('layouts.app')

@section('title', $exam->title . ' — جارٍ الحل')
@include('estidama.lms.partials.learning-assets')

@section('content')
@php($answers = $attempt->answers ?? [])
<div class="es-page">
    <x-kfs.page-hero size="compact" :title="$exam->title" kicker="جارٍ الحل" icon="fa-solid fa-pen-to-square" :wave="false"
        :crumbs="$program ? ['تعلّمي' => route('lms.dashboard'), \Illuminate\Support\Str::limit($program->title, 40) => route('lms.course', $program)] : ['تعلّمي' => route('lms.dashboard')]" />

    <div class="es-exam-bar">
        <div class="container es-exam-bar__inner">
            <div>
                <div class="es-exam-bar__title">{{ $exam->title }}</div>
                <div class="es-exam-bar__meta">المحاولة {{ $attempt->attempt_number }} · {{ $questions->count() }} سؤالاً · <b data-lms-answered>0</b> مُجاب</div>
            </div>
            @if ($remaining !== null)
                <div class="es-timer" role="timer" aria-live="off" aria-label="الوقت المتبقي" data-lms-timer data-remaining="{{ $remaining }}">--:--</div>
            @endif
        </div>
    </div>

    <div class="container es-wrap-900">
        <div class="es-tight">
            @include('estidama.lms.partials.flash')
            <div class="visually-hidden" aria-live="assertive" data-lms-timer-announce></div>

            <form method="POST" action="{{ route('lms.attempts.submit', $attempt) }}" id="lms-exam-form"
                data-lms-exam data-save-url="{{ route('lms.attempts.save', $attempt) }}">
                @csrf
                @foreach ($questions as $i => $question)
                    <fieldset class="es-question" data-lms-question>
                        <legend>
                            <span class="es-qnum">{{ $i + 1 }}</span>
                            <span class="es-grow">{!! clean($question->question_text) !!}</span>
                            <span class="es-badge">{{ $question->points }} {{ $question->points == 1 ? 'درجة' : 'درجات' }}</span>
                        </legend>
                        @if ($question->isEssay())
                            <label for="q{{ $question->id }}" class="visually-hidden">إجابتك على السؤال {{ $i + 1 }}</label>
                            <textarea id="q{{ $question->id }}" name="answers[{{ $question->id }}]" maxlength="5000" placeholder="اكتب إجابتك هنا…">{{ $answers[$question->id] ?? '' }}</textarea>
                        @else
                            @foreach ($question->display_options as $j => $option)
                                <label class="es-option" for="q{{ $question->id }}_{{ $j }}">
                                    <input type="radio" id="q{{ $question->id }}_{{ $j }}" name="answers[{{ $question->id }}]" value="{{ $option }}"
                                        @checked(($answers[$question->id] ?? null) === $option)>
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        @endif
                    </fieldset>
                @endforeach

                <div class="es-card es-exam-submit">
                    <span class="es-save-status" data-lms-save-status aria-live="polite">تُحفظ إجاباتك تلقائيًا.</span>
                    <button type="submit" class="es-btn es-btn--lg" data-lms-submit><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> تسليم الإجابات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
