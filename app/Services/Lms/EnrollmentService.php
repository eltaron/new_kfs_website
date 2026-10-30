<?php

namespace App\Services\Lms;

use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\TrainingApplication;
use Modules\Estidama\Models\TrainingProgram;
use App\Models\User;
use App\Services\Payments\Money;
use Illuminate\Support\Facades\DB;

/**
 * Application -> approval -> Enrollment, and direct enrollment for programs
 * that do not require an application. Paid programs create an "unpaid"
 * enrollment that is settled through route('payments.pay').
 */
class EnrollmentService
{
    public function activeEnrollment(User $user, TrainingProgram $program): ?Enrollment
    {
        return Enrollment::where('user_id', $user->getKey())
            ->where('training_program_id', $program->getKey())
            ->whereIn('status', ['pending', 'approved', 'completed'])
            ->latest('id')
            ->first();
    }

    /** Enrollment created when staff approve a TrainingApplication. */
    public function enrollFromApplication(TrainingApplication $application): ?Enrollment
    {
        $user = $application->resolveUser();
        if (! $user) {
            return null; // no account yet: staff can enroll manually later
        }

        $program = $application->trainingProgram;
        if (! $program) {
            return null;
        }

        return DB::transaction(function () use ($application, $user, $program) {
            $enrollment = $this->activeEnrollment($user, $program);

            if ($enrollment) {
                if ($enrollment->status === 'pending') {
                    $enrollment->forceFill(['status' => 'approved'])->save();
                }
                if (! $enrollment->training_application_id) {
                    $enrollment->forceFill(['training_application_id' => $application->getKey()])->save();
                }
            } else {
                $enrollment = $this->create($user, $program, 'approved', $application);
            }

            if (! $application->user_id) {
                $application->forceFill(['user_id' => $user->getKey()])->saveQuietly();
            }

            return $enrollment;
        });
    }

    /** "سجّل الآن" for programs without an application step. */
    public function enrollDirect(User $user, TrainingProgram $program): Enrollment
    {
        return DB::transaction(function () use ($user, $program) {
            return $this->activeEnrollment($user, $program)
                ?? $this->create($user, $program, 'approved');
        });
    }

    public function create(User $user, TrainingProgram $program, string $status = 'approved', ?TrainingApplication $application = null): Enrollment
    {
        $paid = ! $program->isFree();

        return Enrollment::create([
            'user_id' => $user->getKey(),
            'training_program_id' => $program->getKey(),
            'training_application_id' => $application?->getKey(),
            'status' => $status,
            'payment_status' => $paid ? 'unpaid' : 'free',
            'amount' => $paid ? Money::format($program->price) : '0.00',
            'enrolled_at' => now(),
        ]);
    }
}
