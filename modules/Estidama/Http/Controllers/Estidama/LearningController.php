<?php

namespace Modules\Estidama\Http\Controllers\Estidama;

use App\Http\Controllers\Controller;
use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\ExamSubmission;
use Modules\Estidama\Models\ProgramLesson;
use Modules\Estidama\Models\TrainingCertificate;
use Modules\Estidama\Models\TrainingProgram;
use App\Services\Lms\EnrollmentService;
use App\Services\Lms\ExamService;
use App\Services\Lms\LmsAccess;
use App\Services\Lms\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Learner side of the Estidama LMS: program page, "تعلّمي" dashboard, course
 * player, progress tracking and protected lesson media.
 */
class LearningController extends Controller
{
    public function __construct(
        protected ProgressService $progress,
        protected EnrollmentService $enrollments,
        protected ExamService $exams,
    ) {
    }

    /** Public program page with syllabus and the right call-to-action. */
    public function show(Request $request, TrainingProgram $program)
    {
        $program->load(['trainingCenter', 'instructor', 'finalExam']);
        $modules = $program->programModules()->where('is_published', true)
            ->with(['lessons' => fn ($q) => $q->where('is_published', true)])
            ->get()
            ->filter(fn ($m) => $m->lessons->isNotEmpty());

        $user = $request->user();
        $enrollment = $user ? $this->enrollments->activeEnrollment($user, $program) : null;
        $application = $user ? $program->applications()
            ->where(fn ($q) => $q->where('user_id', $user->getKey())->orWhere('national_id', $user->national_id ?? '-'))
            ->latest('id')->first() : null;

        return view('estidama.lms.program', [
            'program' => $program,
            'modules' => $modules,
            'enrollment' => $enrollment,
            'application' => $application,
            'lessonsCount' => $modules->sum(fn ($m) => $m->lessons->count()),
            'totalMinutes' => $program->totalMinutes(),
        ]);
    }

    /** Direct enrollment (programs without an application step). */
    public function enroll(Request $request, TrainingProgram $program)
    {
        $user = $request->user();

        if ($program->requires_application) {
            return redirect()->route('estidama.apply', $program);
        }
        if ($program->status === 'closed') {
            return back()->with('error', 'التسجيل في هذا البرنامج مغلق حاليًا.');
        }

        $enrollment = $this->enrollments->enrollDirect($user, $program);

        if ($enrollment->needsPayment()) {
            return redirect()->route('payments.pay', ['type' => $enrollment->getMorphClass(), 'id' => $enrollment->getKey()]);
        }

        return redirect()->route('lms.course', $program)->with('success', 'تم تسجيلك في البرنامج بنجاح. بالتوفيق!');
    }

    /** "تعلّمي": the learner dashboard. */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $enrollments = Enrollment::with(['trainingProgram.trainingCenter', 'certificate', 'lastLesson'])
            ->where('user_id', $user->getKey())
            ->latest('last_activity_at')
            ->latest('id')
            ->get();

        $certificates = TrainingCertificate::with('trainingProgram')
            ->where('user_id', $user->getKey())->whereNull('revoked_at')->latest('issued_at')->get();

        $attempts = ExamSubmission::with('exam')
            ->where('user_id', $user->getKey())->latest('id')->take(8)->get();

        return view('estidama.lms.dashboard', [
            'user' => $user,
            'active' => $enrollments->filter(fn ($e) => $e->status === 'approved'),
            'completed' => $enrollments->filter(fn ($e) => $e->status === 'completed'),
            'pending' => $enrollments->filter(fn ($e) => $e->status === 'pending'),
            'certificates' => $certificates,
            'badges' => $user->badges()->orderByPivot('awarded_at', 'desc')->get(),
            'attempts' => $attempts,
            'applications' => \Modules\Estidama\Models\TrainingApplication::with('trainingProgram')
                ->where(fn ($q) => $q->where('user_id', $user->getKey())->orWhere('national_id', $user->national_id ?? '-'))
                ->whereIn('status', ['pending', 'rejected'])
                ->latest('id')->get(),
        ]);
    }

    /** Resume the course at the right lesson. */
    public function course(Request $request, TrainingProgram $program)
    {
        $enrollment = $this->enrollmentOrRedirect($request, $program);
        if (! $enrollment instanceof Enrollment) {
            return $enrollment;
        }

        $lesson = $this->progress->resumeLesson($enrollment);
        if (! $lesson) {
            return redirect()->route('estidama.program.show', $program)
                ->with('info', 'لم تُنشر دروس هذا البرنامج بعد. سنُعلمك فور إتاحتها.');
        }

        return redirect()->route('lms.lesson', [$program, $lesson]);
    }

    /** Course player. */
    public function lesson(Request $request, TrainingProgram $program, ProgramLesson $lesson)
    {
        abort_unless((int) $lesson->training_program_id === (int) $program->id && $lesson->is_published, 404);

        $enrollment = $this->enrollmentOrRedirect($request, $program);
        if (! $enrollment instanceof Enrollment) {
            return $enrollment;
        }

        $states = $this->progress->lessonStates($enrollment);
        $state = $states->get($lesson->id);
        abort_if($state === null, 404);

        if ($state['locked']) {
            $fallback = $this->progress->resumeLesson($enrollment);

            return redirect()->route('lms.lesson', [$program, $fallback ?? $lesson])->with('error', $state['reason']);
        }

        $progress = $this->progress->touch($enrollment, $lesson);
        $outline = $this->progress->outline($enrollment);
        $neighbours = $this->progress->neighbours($enrollment, $lesson);

        $quiz = null;
        if ($lesson->type === 'quiz' && $lesson->exam) {
            $quiz = [
                'exam' => $lesson->exam,
                'attempts' => $this->exams->attempts($lesson->exam, $request->user()),
                'remaining' => $this->exams->remainingAttempts($lesson->exam, $request->user()),
                'blocker' => $this->exams->startBlocker($lesson->exam, $request->user()),
                'open' => $this->exams->openAttempt($lesson->exam, $request->user()),
            ];
        }

        $enrollment->refresh();

        return view('estidama.lms.player', [
            'program' => $program,
            'enrollment' => $enrollment,
            'lesson' => $lesson,
            'progressRecord' => $progress,
            'completed' => $state['completed'],
            'outline' => $outline,
            'previous' => $neighbours['previous'],
            'next' => $neighbours['next'],
            'nextLocked' => $neighbours['next'] ? ($states->get($neighbours['next']->id)['locked'] ?? false) : false,
            'quiz' => $quiz,
            'finalExam' => $program->finalExam,
            'finalExamReady' => $program->exam_id && $this->progress->allRequiredLessonsDone($enrollment),
            'certificate' => $enrollment->certificate,
        ]);
    }

    public function complete(Request $request, TrainingProgram $program, ProgramLesson $lesson)
    {
        abort_unless((int) $lesson->training_program_id === (int) $program->id, 404);
        $enrollment = $this->enrollmentOrRedirect($request, $program);
        if (! $enrollment instanceof Enrollment) {
            return $enrollment;
        }

        $state = $this->progress->state($enrollment, $lesson);
        abort_if($state === null, 404);
        if ($state['locked']) {
            return back()->with('error', $state['reason']);
        }
        if ($lesson->type === 'quiz') {
            return back()->with('error', 'يكتمل هذا الدرس تلقائيًا عند اجتياز الاختبار.');
        }

        $this->progress->markComplete($enrollment, $lesson);
        $enrollment->refresh();

        if ($enrollment->status === 'completed' && $enrollment->certificate) {
            return redirect()->route('lms.certificates.show', $enrollment->certificate->serial)
                ->with('success', 'مبروك! أتممت البرنامج وصدرت شهادتك.');
        }

        $next = $this->progress->neighbours($enrollment, $lesson)['next'];
        $nextState = $next ? $this->progress->state($enrollment, $next) : null;

        if ($next && $nextState && ! $nextState['locked']) {
            return redirect()->route('lms.lesson', [$program, $next])->with('success', 'أحسنت! تم تسجيل إتمام الدرس.');
        }

        return redirect()->route('lms.lesson', [$program, $lesson])->with('success', 'أحسنت! تم تسجيل إتمام الدرس.');
    }

    /** Player heartbeat: video position and personal notes (JSON). */
    public function saveProgress(Request $request, TrainingProgram $program, ProgramLesson $lesson)
    {
        abort_unless((int) $lesson->training_program_id === (int) $program->id, 404);
        $data = $request->validate([
            'position' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $enrollment = $this->enrollmentOrRedirect($request, $program);
        abort_unless($enrollment instanceof Enrollment, 403);

        $state = $this->progress->state($enrollment, $lesson);
        abort_if($state === null || $state['locked'], 403);

        $record = $this->progress->savePosition(
            $enrollment,
            $lesson,
            $data['position'] ?? null,
            $data['notes'] ?? null,
            $request->has('notes'),
        );

        return response()->json(['saved' => true, 'position' => $record->last_position_seconds]);
    }

    /** Streams an uploaded lesson file (video / PDF / captions) to authorised viewers only. */
    public function media(Request $request, ProgramLesson $lesson, string $kind)
    {
        abort_unless(in_array($kind, ['file', 'captions'], true), 404);
        $path = $kind === 'captions' ? $lesson->captions_path : $lesson->file_path;
        abort_if(blank($path) || str_contains($path, '..'), 404);

        $user = $request->user();
        $allowed = LmsAccess::canManageProgram($user, $lesson->training_program_id);

        if (! $allowed) {
            $enrollment = Enrollment::where('user_id', $user->getKey())
                ->where('training_program_id', $lesson->training_program_id)
                ->whereIn('status', ['approved', 'completed'])->latest('id')->first();
            if ($enrollment && $enrollment->canAccessContent()) {
                $state = $this->progress->state($enrollment, $lesson);
                $allowed = $state !== null && ! $state['locked'];
            }
        }
        abort_unless($allowed, 403);

        $disk = Storage::disk('local')->exists($path) ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($path), 404);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ];
        if ($kind === 'captions') {
            $headers['Content-Type'] = 'text/vtt; charset=utf-8';
        }

        // BinaryFileResponse answers HTTP range requests, needed for seeking in <video>.
        return response()->file(Storage::disk($disk)->path($path), $headers);
    }

    /** Learner's manually-uploaded certificate file (EnrollmentResource upload, private disk). */
    public function enrollmentCertificateFile(Request $request, Enrollment $enrollment)
    {
        $user = $request->user();
        abort_unless(
            (string) $enrollment->user_id === (string) $user->getKey()
            || LmsAccess::canManageProgram($user, $enrollment->training_program_id),
            403
        );
        $path = $enrollment->certificate_path;
        abort_if(blank($path) || str_contains($path, '..'), 404);
        $disk = Storage::disk('local')->exists($path) ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, basename($path), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Returns the learner's usable enrollment or a redirect explaining why the
     * course cannot be opened (not enrolled, pending, unpaid).
     */
    protected function enrollmentOrRedirect(Request $request, TrainingProgram $program): Enrollment|\Illuminate\Http\RedirectResponse
    {
        $enrollment = $this->enrollments->activeEnrollment($request->user(), $program);

        if (! $enrollment) {
            return redirect()->route('estidama.program.show', $program)->with('error', 'يجب التسجيل في البرنامج أولاً.');
        }
        if ($enrollment->status === 'pending') {
            return redirect()->route('lms.dashboard')->with('info', 'تسجيلك في البرنامج قيد المراجعة.');
        }
        if ($enrollment->needsPayment()) {
            return redirect()->route('estidama.program.show', $program)->with('info', 'يرجى سداد رسوم البرنامج لبدء التعلم.');
        }

        return $enrollment;
    }
}
