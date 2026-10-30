<?php

namespace App\Services\Lms;

use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\LessonProgress;
use Modules\Estidama\Models\ProgramLesson;
use Modules\Estidama\Models\TrainingProgram;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lesson unlocking (sequential completion, prerequisites, drip by section) and
 * progress bookkeeping for an enrollment.
 */
class ProgressService
{
    public function __construct(protected CertificateService $certificates)
    {
    }

    /**
     * Course outline with the learner's state.
     *
     * @return Collection<int, array{module: \Modules\Estidama\Models\ProgramModule, lessons: list<array{lesson: ProgramLesson, completed: bool, locked: bool, reason: ?string}>}>
     */
    public function outline(Enrollment $enrollment): Collection
    {
        $program = $enrollment->trainingProgram;
        $states = $this->lessonStates($enrollment);
        $modules = $program->programModules()->where('is_published', true)->get();

        return $modules->map(fn ($module) => [
            'module' => $module,
            'lessons' => $states->filter(fn ($s) => $s['lesson']->program_module_id === $module->id)->values()->all(),
        ])->filter(fn ($section) => count($section['lessons']) > 0)->values();
    }

    /**
     * Ordered lesson states keyed by lesson id.
     *
     * @return Collection<int, array{lesson: ProgramLesson, completed: bool, locked: bool, reason: ?string}>
     */
    public function lessonStates(Enrollment $enrollment): Collection
    {
        $program = $enrollment->trainingProgram;
        $lessons = $program->orderedLessons()->load('module', 'prerequisite');
        $done = $this->completedIds($enrollment);
        $start = $this->startDate($enrollment);

        $states = collect();
        $previousRequiredDone = true;

        foreach ($lessons as $lesson) {
            $completed = isset($done[$lesson->id]);
            $reason = null;

            $days = $lesson->module?->unlock_after_days;
            if ($days && $start->copy()->addDays($days)->isFuture()) {
                $reason = 'يُتاح هذا الدرس بتاريخ ' . $start->copy()->addDays($days)->format('Y-m-d');
            } elseif ($lesson->prerequisite_lesson_id && ! isset($done[$lesson->prerequisite_lesson_id])) {
                $reason = 'أكمل درس «' . ($lesson->prerequisite?->title ?? 'السابق') . '» أولاً';
            } elseif ($program->sequential && ! $previousRequiredDone) {
                $reason = 'أكمل الدروس السابقة بالترتيب أولاً';
            }

            if ($completed) {
                $reason = null; // a finished lesson always stays reviewable
            }

            $states->put($lesson->id, [
                'lesson' => $lesson,
                'completed' => $completed,
                'locked' => $reason !== null,
                'reason' => $reason,
            ]);

            if ($lesson->is_required && ! $completed) {
                $previousRequiredDone = false;
            }
        }

        return $states;
    }

    /** @return array<int, true> */
    public function completedIds(Enrollment $enrollment): array
    {
        return LessonProgress::where('enrollment_id', $enrollment->getKey())
            ->whereNotNull('completed_at')
            ->pluck('program_lesson_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }

    public function startDate(Enrollment $enrollment): Carbon
    {
        return Carbon::parse($enrollment->paid_at ?? $enrollment->enrolled_at ?? $enrollment->created_at ?? now());
    }

    public function state(Enrollment $enrollment, ProgramLesson $lesson): ?array
    {
        return $this->lessonStates($enrollment)->get($lesson->id);
    }

    /** The lesson to resume: last opened (if accessible), else first unfinished unlocked lesson. */
    public function resumeLesson(Enrollment $enrollment): ?ProgramLesson
    {
        $states = $this->lessonStates($enrollment);

        if ($enrollment->last_lesson_id && ($s = $states->get($enrollment->last_lesson_id)) && ! $s['locked'] && ! $s['completed']) {
            return $s['lesson'];
        }

        $next = $states->first(fn ($s) => ! $s['locked'] && ! $s['completed']);

        return $next['lesson'] ?? ($states->first()['lesson'] ?? null);
    }

    /** Neighbouring lessons for prev/next navigation. */
    public function neighbours(Enrollment $enrollment, ProgramLesson $lesson): array
    {
        $ids = $this->lessonStates($enrollment)->keys()->values();
        $index = $ids->search($lesson->id);

        return [
            'previous' => $index !== false && $index > 0 ? ProgramLesson::find($ids[$index - 1]) : null,
            'next' => $index !== false && $index < $ids->count() - 1 ? ProgramLesson::find($ids[$index + 1]) : null,
        ];
    }

    /** Records that the learner opened a lesson (resume point). */
    public function touch(Enrollment $enrollment, ProgramLesson $lesson): LessonProgress
    {
        $progress = LessonProgress::firstOrCreate(
            ['enrollment_id' => $enrollment->getKey(), 'program_lesson_id' => $lesson->getKey()],
            ['user_id' => $enrollment->user_id, 'started_at' => now()]
        );

        $enrollment->forceFill(['last_lesson_id' => $lesson->getKey(), 'last_activity_at' => now()])->save();

        return $progress;
    }

    public function savePosition(Enrollment $enrollment, ProgramLesson $lesson, ?int $seconds, ?string $notes = null, bool $updateNotes = false): LessonProgress
    {
        $progress = $this->touch($enrollment, $lesson);
        if ($seconds !== null) {
            $progress->last_position_seconds = max(0, min($seconds, 60 * 60 * 24));
        }
        if ($updateNotes) {
            $progress->notes = $notes === null ? null : mb_substr($notes, 0, 5000);
        }
        $progress->save();

        return $progress;
    }

    public function markComplete(Enrollment $enrollment, ProgramLesson $lesson): LessonProgress
    {
        $progress = $this->touch($enrollment, $lesson);
        if (! $progress->completed_at) {
            $progress->forceFill(['completed_at' => now()])->save();
        }

        $this->refresh($enrollment);

        return $progress;
    }

    /** Recomputes the progress percentage and checks course completion. */
    public function refresh(Enrollment $enrollment): Enrollment
    {
        $enrollment->refresh();
        $required = $this->requiredLessonIds($enrollment->trainingProgram);
        $done = $this->completedIds($enrollment);

        $percent = $required === []
            ? 0
            : (int) floor(count(array_filter($required, fn ($id) => isset($done[$id]))) / count($required) * 100);

        if ($enrollment->status === 'completed') {
            $percent = 100;
        }

        $enrollment->forceFill(['progress_percent' => $percent, 'last_activity_at' => now()])->save();

        $this->certificates->checkCompletion($enrollment);

        return $enrollment->refresh();
    }

    /** @return list<int> */
    public function requiredLessonIds(TrainingProgram $program): array
    {
        return $program->orderedLessons()->where('is_required', true)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function allRequiredLessonsDone(Enrollment $enrollment): bool
    {
        $done = $this->completedIds($enrollment);
        foreach ($this->requiredLessonIds($enrollment->trainingProgram) as $id) {
            if (! isset($done[$id])) {
                return false;
            }
        }

        return true;
    }
}
