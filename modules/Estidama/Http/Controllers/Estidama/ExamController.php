<?php

namespace Modules\Estidama\Http\Controllers\Estidama;

use App\Http\Controllers\Controller;
use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\Exam;
use Modules\Estidama\Models\ExamSubmission;
use Modules\Estidama\Models\ProgramLesson;
use Modules\Estidama\Models\TrainingProgram;
use App\Services\Lms\EnrollmentService;
use App\Services\Lms\ExamService;
use App\Services\Lms\ProgressService;
use Illuminate\Http\Request;

/**
 * Final exams and lesson quizzes: rules page, timed attempt, submission and
 * answer review.
 */
class ExamController extends Controller
{
    public function __construct(
        protected ExamService $exams,
        protected ProgressService $progress,
        protected EnrollmentService $enrollments,
    ) {
    }

    public function show(Request $request, TrainingProgram $program, Exam $exam)
    {
        [$enrollment, $lesson, $error] = $this->context($request, $program, $exam, $request->integer('lesson') ?: null);
        if ($error) {
            return $error;
        }

        $user = $request->user();

        return view('estidama.lms.exam-intro', [
            'program' => $program,
            'exam' => $exam,
            'lesson' => $lesson,
            'enrollment' => $enrollment,
            'questionsCount' => $exam->questions()->count(),
            'attempts' => $this->exams->attempts($exam, $user),
            'remaining' => $this->exams->remainingAttempts($exam, $user),
            'blocker' => $this->exams->startBlocker($exam, $user),
            'open' => $this->exams->openAttempt($exam, $user),
        ]);
    }

    public function start(Request $request, TrainingProgram $program, Exam $exam)
    {
        [$enrollment, $lesson, $error] = $this->context($request, $program, $exam, $request->integer('lesson') ?: null);
        if ($error) {
            return $error;
        }

        try {
            $attempt = $this->exams->start($exam, $request->user(), $enrollment, $lesson);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('lms.attempts.show', $attempt);
    }

    public function take(Request $request, ExamSubmission $attempt)
    {
        $this->authorizeAttempt($request, $attempt);

        if (! $attempt->isInProgress()) {
            return redirect()->route('lms.attempts.review', $attempt);
        }
        if ($this->exams->isExpired($attempt, ExamService::GRACE_SECONDS)) {
            $this->exams->finalize($attempt, null);

            return redirect()->route('lms.attempts.review', $attempt)->with('info', 'انتهى وقت الاختبار وتم تسليم إجاباتك المحفوظة تلقائيًا.');
        }

        return view('estidama.lms.exam-take', [
            'attempt' => $attempt,
            'exam' => $attempt->exam,
            'program' => $this->programFor($attempt),
            'questions' => $this->exams->questionsFor($attempt),
            'remaining' => $attempt->expires_at ? $attempt->remainingSeconds() : null,
        ]);
    }

    public function save(Request $request, ExamSubmission $attempt)
    {
        $this->authorizeAttempt($request, $attempt);
        $data = $request->validate(['answers' => ['nullable', 'array', 'max:500'], 'answers.*' => ['nullable', 'string', 'max:5000']]);
        $this->exams->saveAnswers($attempt, $data['answers'] ?? []);

        return response()->json(['saved' => true, 'remaining' => $attempt->expires_at ? $attempt->fresh()->remainingSeconds() : null]);
    }

    public function submit(Request $request, ExamSubmission $attempt)
    {
        $this->authorizeAttempt($request, $attempt);
        $data = $request->validate(['answers' => ['nullable', 'array', 'max:500'], 'answers.*' => ['nullable', 'string', 'max:5000']]);

        $attempt = $this->exams->submit($attempt, $data['answers'] ?? []);

        $message = match (true) {
            $attempt->status === ExamSubmission::STATUS_NEEDS_GRADING => 'تم تسليم إجاباتك، وستظهر النتيجة النهائية بعد تصحيح المدرب للأسئلة المقالية.',
            $attempt->passed => 'مبروك! اجتزت الاختبار بنسبة ' . $attempt->score . '%.',
            default => 'لم تبلغ درجة النجاح هذه المرة (' . $attempt->score . '%). راجع الدروس وحاول مجددًا.',
        };

        return redirect()->route('lms.attempts.review', $attempt)->with($attempt->passed ? 'success' : 'info', $message);
    }

    public function review(Request $request, ExamSubmission $attempt)
    {
        $this->authorizeAttempt($request, $attempt);
        if ($attempt->isInProgress()) {
            return redirect()->route('lms.attempts.show', $attempt);
        }

        $exam = $attempt->exam;
        $program = $this->programFor($attempt);
        $enrollment = $program ? $this->enrollments->activeEnrollment($request->user(), $program) : null;

        return view('estidama.lms.exam-review', [
            'attempt' => $attempt,
            'exam' => $exam,
            'program' => $program,
            'enrollment' => $enrollment?->fresh(['certificate']),
            'questions' => $this->exams->questionsFor($attempt),
            'showAnswers' => (bool) $exam->allow_review,
            'remaining' => $this->exams->remainingAttempts($exam, $request->user()),
            'blocker' => $this->exams->startBlocker($exam, $request->user()),
        ]);
    }

    // ------------------------------------------------------------------

    /**
     * Validates that the exam belongs to the program and that the learner may
     * sit it now. Returns [enrollment, lesson, errorRedirect].
     */
    protected function context(Request $request, TrainingProgram $program, Exam $exam, ?int $lessonId): array
    {
        $enrollment = $this->enrollments->activeEnrollment($request->user(), $program);
        if (! $enrollment || ! $enrollment->canAccessContent()) {
            return [null, null, redirect()->route('estidama.program.show', $program)->with('error', 'يجب أن تكون مسجلاً في البرنامج لأداء الاختبار.')];
        }

        $lesson = $lessonId ? ProgramLesson::where('training_program_id', $program->id)->where('exam_id', $exam->id)->find($lessonId) : null;
        $isFinal = (int) $program->exam_id === (int) $exam->id;
        $belongs = $isFinal || $lesson !== null
            || ProgramLesson::where('training_program_id', $program->id)->where('exam_id', $exam->id)->exists()
            || (int) $exam->training_program_id === (int) $program->id;
        abort_unless($belongs, 404);

        if ($lesson) {
            $state = $this->progress->state($enrollment, $lesson);
            if (! $state || $state['locked']) {
                return [null, null, redirect()->route('lms.course', $program)->with('error', $state['reason'] ?? 'هذا الدرس غير متاح بعد.')];
            }
        } elseif ($isFinal && ! $this->progress->allRequiredLessonsDone($enrollment)) {
            return [null, null, redirect()->route('lms.course', $program)->with('error', 'أكمل جميع الدروس المطلوبة أولاً لفتح الامتحان النهائي.')];
        }

        return [$enrollment, $lesson, null];
    }

    protected function authorizeAttempt(Request $request, ExamSubmission $attempt): void
    {
        abort_unless((string) $attempt->user_id === (string) $request->user()->getKey(), 403);
    }

    protected function programFor(ExamSubmission $attempt): ?TrainingProgram
    {
        if ($attempt->enrollment_id && ($e = Enrollment::find($attempt->enrollment_id))) {
            return $e->trainingProgram;
        }

        return $attempt->exam?->trainingProgram;
    }
}
