<?php

namespace App\Services\Lms;

use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\Exam;
use Modules\Estidama\Models\ExamQuestion;
use Modules\Estidama\Models\ExamSubmission;
use Modules\Estidama\Models\ProgramLesson;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Timed exam / quiz attempts: attempt limits, question & option
 * randomisation, server-side timer enforcement, auto + manual grading.
 */
class ExamService
{
    /** Seconds accepted after the timer ends (network latency / auto-submit). */
    public const GRACE_SECONDS = 30;

    public function __construct(protected ProgressService $progress, protected CertificateService $certificates)
    {
    }

    public function attempts(Exam $exam, User $user): Collection
    {
        return ExamSubmission::where('exam_id', $exam->getKey())
            ->where('user_id', $user->getKey())
            ->orderBy('attempt_number')
            ->get();
    }

    public function usedAttempts(Exam $exam, User $user): int
    {
        return ExamSubmission::where('exam_id', $exam->getKey())->where('user_id', $user->getKey())->count();
    }

    public function remainingAttempts(Exam $exam, User $user): ?int
    {
        return $exam->max_attempts ? max(0, $exam->max_attempts - $this->usedAttempts($exam, $user)) : null;
    }

    public function hasPassed(Exam $exam, User $user): bool
    {
        return ExamSubmission::where('exam_id', $exam->getKey())->where('user_id', $user->getKey())
            ->where('status', ExamSubmission::STATUS_SUBMITTED)->where('passed', true)->exists();
    }

    public function openAttempt(Exam $exam, User $user): ?ExamSubmission
    {
        $attempt = ExamSubmission::where('exam_id', $exam->getKey())->where('user_id', $user->getKey())
            ->where('status', ExamSubmission::STATUS_IN_PROGRESS)->latest('id')->first();

        if ($attempt && $this->isExpired($attempt)) {
            $this->finalize($attempt, null);

            return null;
        }

        return $attempt;
    }

    /** Why a new attempt cannot start (null = allowed). */
    public function startBlocker(Exam $exam, User $user): ?string
    {
        if ($exam->questions()->count() === 0) {
            return 'لم تُضف أسئلة لهذا الاختبار بعد.';
        }
        if ($this->hasPassed($exam, $user)) {
            return 'لقد اجتزت هذا الاختبار بنجاح بالفعل.';
        }
        if ($exam->max_attempts && $this->usedAttempts($exam, $user) >= $exam->max_attempts) {
            return 'استنفدت جميع المحاولات المتاحة لهذا الاختبار.';
        }
        if (ExamSubmission::where('exam_id', $exam->getKey())->where('user_id', $user->getKey())
            ->where('status', ExamSubmission::STATUS_NEEDS_GRADING)->exists()) {
            return 'محاولتك السابقة بانتظار تصحيح المدرب.';
        }

        return null;
    }

    public function start(Exam $exam, User $user, ?Enrollment $enrollment = null, ?ProgramLesson $lesson = null): ExamSubmission
    {
        return DB::transaction(function () use ($exam, $user, $enrollment, $lesson) {
            if ($open = $this->openAttempt($exam, $user)) {
                return $open;
            }

            if ($blocker = $this->startBlocker($exam, $user)) {
                throw new \DomainException($blocker);
            }

            $questions = $exam->questions()->get();
            $order = $questions->pluck('id')->all();
            if ($exam->shuffle_questions) {
                shuffle($order);
            }

            $optionOrder = [];
            foreach ($questions as $question) {
                $options = $question->optionTexts();
                if ($exam->shuffle_options && $question->question_type === 'mcq') {
                    shuffle($options);
                }
                $optionOrder[$question->id] = $options;
            }

            $now = now();

            return ExamSubmission::create([
                'exam_id' => $exam->getKey(),
                'user_id' => $user->getKey(),
                'enrollment_id' => $enrollment?->getKey(),
                'program_lesson_id' => $lesson?->getKey(),
                'attempt_number' => $this->usedAttempts($exam, $user) + 1,
                'status' => ExamSubmission::STATUS_IN_PROGRESS,
                'started_at' => $now,
                'expires_at' => $exam->duration_minutes > 0 ? $now->copy()->addMinutes($exam->duration_minutes) : null,
                'question_order' => $order,
                'option_order' => $optionOrder,
                'answers' => [],
                'score' => 0,
                'passed' => false,
            ]);
        });
    }

    public function isExpired(ExamSubmission $attempt, int $grace = 0): bool
    {
        return $attempt->expires_at !== null && now()->greaterThan($attempt->expires_at->copy()->addSeconds($grace));
    }

    /** Ordered questions of an attempt with their (possibly shuffled) options. */
    public function questionsFor(ExamSubmission $attempt): Collection
    {
        $questions = ExamQuestion::where('exam_id', $attempt->exam_id)->get()->keyBy('id');
        $order = $attempt->question_order ?: $questions->keys()->all();
        $options = $attempt->option_order ?? [];

        return collect($order)
            ->map(fn ($id) => $questions->get($id))
            ->filter()
            ->map(function (ExamQuestion $q) use ($options) {
                $q->setAttribute('display_options', $options[$q->id] ?? $options[(string) $q->id] ?? $q->optionTexts());

                return $q;
            })
            ->values();
    }

    /** Autosave while the timer runs. */
    public function saveAnswers(ExamSubmission $attempt, array $answers): void
    {
        if (! $attempt->isInProgress() || $this->isExpired($attempt, self::GRACE_SECONDS)) {
            return;
        }
        $attempt->forceFill(['answers' => $this->sanitizeAnswers($attempt, $answers)])->save();
    }

    /**
     * Submits an attempt. Answers sent after the timer (+grace) are ignored and
     * the last autosaved answers are graded instead.
     */
    public function submit(ExamSubmission $attempt, array $answers): ExamSubmission
    {
        if (! $attempt->isInProgress()) {
            return $attempt;
        }

        $late = $this->isExpired($attempt, self::GRACE_SECONDS);

        return $this->finalize($attempt, $late ? null : $answers);
    }

    public function finalize(ExamSubmission $attempt, ?array $answers): ExamSubmission
    {
        $attempt = DB::transaction(function () use ($attempt, $answers) {
            $attempt = ExamSubmission::whereKey($attempt->getKey())->lockForUpdate()->first();
            if (! $attempt->isInProgress()) {
                return $attempt;
            }

            $final = $answers !== null ? $this->sanitizeAnswers($attempt, $answers) : ($attempt->answers ?? []);
            $attempt->answers = $final;
            $attempt->submitted_at = now();
            $this->grade($attempt);
            $attempt->save();

            return $attempt;
        });

        $this->afterGrading($attempt);

        return $attempt;
    }

    /** Computes points/score/passed. Essay questions use manual points from answers["_manual"]. */
    public function grade(ExamSubmission $attempt): void
    {
        $questions = ExamQuestion::where('exam_id', $attempt->exam_id)->get();
        $answers = $attempt->answers ?? [];
        $manual = $answers['_manual'] ?? [];

        $total = 0.0;
        $earned = 0.0;
        $pendingEssay = false;

        foreach ($questions as $question) {
            $points = max(1, (int) $question->points);
            $total += $points;
            $answer = $answers[$question->id] ?? $answers[(string) $question->id] ?? null;

            if ($question->isEssay()) {
                if (array_key_exists($question->id, $manual) || array_key_exists((string) $question->id, $manual)) {
                    $earned += min($points, max(0, (float) ($manual[$question->id] ?? $manual[(string) $question->id])));
                } elseif (filled($answer)) {
                    $pendingEssay = true;
                }
            } elseif ($question->isCorrect($answer)) {
                $earned += $points;
            }
        }

        $exam = $attempt->exam;
        $score = $total > 0 ? (int) round($earned / $total * 100) : 0;

        $attempt->earned_points = $earned;
        $attempt->total_points = $total;
        $attempt->score = $score;
        $attempt->status = $pendingEssay ? ExamSubmission::STATUS_NEEDS_GRADING : ExamSubmission::STATUS_SUBMITTED;
        $attempt->passed = ! $pendingEssay && $score >= (int) ($exam?->passing_score ?? 50);
    }

    /** Trainer grading of essay answers. */
    public function gradeManually(ExamSubmission $attempt, array $essayPoints, ?string $feedback, User $grader): ExamSubmission
    {
        $answers = $attempt->answers ?? [];
        $answers['_manual'] = array_map(fn ($p) => (float) $p, array_filter($essayPoints, fn ($p) => $p !== null && $p !== ''));
        $attempt->answers = $answers;
        if ($attempt->isInProgress()) {
            $attempt->submitted_at = now();
        }
        $this->grade($attempt);
        // Essays without manual points left: grading is complete once the trainer saves.
        if ($attempt->status === ExamSubmission::STATUS_NEEDS_GRADING) {
            $attempt->status = ExamSubmission::STATUS_SUBMITTED;
            $attempt->passed = $attempt->score >= (int) ($attempt->exam?->passing_score ?? 50);
        }
        $attempt->graded_by = $grader->getKey();
        $attempt->graded_at = now();
        $attempt->feedback = $feedback;
        $attempt->save();

        $this->afterGrading($attempt);

        return $attempt;
    }

    protected function afterGrading(ExamSubmission $attempt): void
    {
        if ($attempt->status !== ExamSubmission::STATUS_SUBMITTED || ! $attempt->passed) {
            return;
        }

        $enrollment = $attempt->enrollment ?? $this->enrollmentForExam($attempt);
        if (! $enrollment) {
            return;
        }

        if ($attempt->program_lesson_id && ($lesson = ProgramLesson::find($attempt->program_lesson_id))) {
            $this->progress->markComplete($enrollment, $lesson);
        } else {
            $this->progress->refresh($enrollment);
        }
    }

    protected function enrollmentForExam(ExamSubmission $attempt): ?Enrollment
    {
        $programId = $attempt->exam?->training_program_id;

        return $programId ? Enrollment::where('user_id', $attempt->user_id)->where('training_program_id', $programId)
            ->whereIn('status', ['approved', 'completed'])->latest('id')->first() : null;
    }

    /** Keeps only answers to questions of this attempt, with values among the offered options. */
    protected function sanitizeAnswers(ExamSubmission $attempt, array $answers): array
    {
        $clean = [];
        foreach ($this->questionsFor($attempt) as $question) {
            $value = $answers[$question->id] ?? $answers[(string) $question->id] ?? null;
            if ($value === null || is_array($value)) {
                continue;
            }
            $value = trim((string) $value);
            if ($question->isEssay()) {
                $clean[$question->id] = mb_substr($value, 0, 5000);
            } elseif (in_array($value, $question->display_options, true)) {
                $clean[$question->id] = $value;
            }
        }

        return $clean;
    }
}
