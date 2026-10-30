<?php

namespace App\Services\Lms;

use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\ExamSubmission;
use Modules\Estidama\Models\LessonProgress;
use Modules\Estidama\Models\TrainingCertificate;
use Illuminate\Support\Facades\DB;

/**
 * Course completion: all required lessons done + final exam passed (when the
 * program has one) => enrollment "completed", certificate issued, badges.
 */
class CertificateService
{
    public function __construct(protected BadgeService $badges)
    {
    }

    public function requirementsMet(Enrollment $enrollment): bool
    {
        $program = $enrollment->trainingProgram;
        $required = $program->orderedLessons()->where('is_required', true)->pluck('id')->all();

        if ($required === [] && ! $program->exam_id) {
            return false; // nothing to complete online: staff complete such programs manually
        }

        $done = LessonProgress::where('enrollment_id', $enrollment->getKey())
            ->whereNotNull('completed_at')
            ->whereIn('program_lesson_id', $required ?: [0])
            ->count();

        if ($done < count($required)) {
            return false;
        }

        return ! $program->exam_id || $this->passedFinalExam($enrollment) !== null;
    }

    public function passedFinalExam(Enrollment $enrollment): ?ExamSubmission
    {
        $examId = $enrollment->trainingProgram?->exam_id;
        if (! $examId) {
            return null;
        }

        return ExamSubmission::where('exam_id', $examId)
            ->where('user_id', $enrollment->user_id)
            ->where('status', ExamSubmission::STATUS_SUBMITTED)
            ->where('passed', true)
            ->orderByDesc('score')
            ->first();
    }

    public function checkCompletion(Enrollment $enrollment): ?TrainingCertificate
    {
        if (! in_array($enrollment->status, ['approved', 'completed'], true) || $enrollment->needsPayment()) {
            return null;
        }
        if (! $this->requirementsMet($enrollment)) {
            return null;
        }

        return $this->complete($enrollment);
    }

    /** Marks the enrollment completed and issues the certificate (idempotent). */
    public function complete(Enrollment $enrollment): ?TrainingCertificate
    {
        $certificate = DB::transaction(function () use ($enrollment) {
            if ($enrollment->status !== 'completed') {
                $enrollment->forceFill([
                    'status' => 'completed',
                    'completed_at' => $enrollment->completed_at ?? now(),
                    'progress_percent' => 100,
                ])->save();
            }

            return $enrollment->trainingProgram->certificate_enabled ? $this->issue($enrollment) : null;
        });

        if ($enrollment->user) {
            $this->badges->evaluate($enrollment->user);
        }

        return $certificate;
    }

    public function issue(Enrollment $enrollment): TrainingCertificate
    {
        if ($existing = TrainingCertificate::where('enrollment_id', $enrollment->getKey())->first()) {
            return $existing;
        }

        $exam = $this->passedFinalExam($enrollment);

        return TrainingCertificate::create([
            'serial' => TrainingCertificate::generateSerial(),
            'enrollment_id' => $enrollment->getKey(),
            'user_id' => $enrollment->user_id,
            'training_program_id' => $enrollment->training_program_id,
            'exam_submission_id' => $exam?->getKey(),
            'holder_name' => $enrollment->user?->name ?? 'متدرب',
            'program_title' => $enrollment->trainingProgram?->title ?? '',
            'final_score' => $exam?->score,
            'issued_at' => now(),
        ]);
    }
}
