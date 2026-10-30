@php($answers = $attempt->answers ?? [])
@php($manual = $answers['_manual'] ?? [])
<div class="space-y-4" dir="rtl">
    <div class="flex flex-wrap gap-4 text-sm">
        <span>المتدرب: <strong>{{ $attempt->user?->name }}</strong></span>
        <span>المحاولة: <strong>{{ $attempt->attempt_number }}</strong></span>
        <span>الدرجة: <strong>{{ $attempt->score }}%</strong> ({{ rtrim(rtrim(number_format($attempt->earned_points, 2), '0'), '.') }} / {{ rtrim(rtrim(number_format($attempt->total_points, 2), '0'), '.') }})</span>
        <span>الحالة: <strong>{{ $attempt->statusLabel() }}</strong></span>
    </div>
    @if ($attempt->feedback)
        <div class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-white/5">ملاحظات المدرب: {{ $attempt->feedback }}</div>
    @endif
    @foreach ($questions as $i => $question)
        @php($given = $answers[$question->id] ?? null)
        <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
            <div class="mb-2 text-sm font-semibold">{{ $i + 1 }}. {{ strip_tags($question->question_text) }}
                <span class="text-xs text-gray-500">({{ $question->points }} درجة)</span></div>
            @if ($question->isEssay())
                <div class="whitespace-pre-line rounded bg-gray-50 p-2 text-sm dark:bg-white/5">{{ $given ?: '— لم يُجب —' }}</div>
                <div class="mt-1 text-xs text-gray-500">الدرجة اليدوية: {{ $manual[$question->id] ?? 'لم يُصحح بعد' }}</div>
            @else
                <div class="text-sm">
                    إجابة المتدرب:
                    <strong @class(['text-success-600' => $question->isCorrect($given), 'text-danger-600' => ! $question->isCorrect($given)])>{{ $given ?? '— لم يُجب —' }}</strong>
                </div>
                <div class="text-xs text-gray-500">الإجابة الصحيحة: {{ $question->correct_answer }}</div>
            @endif
        </div>
    @endforeach
</div>
