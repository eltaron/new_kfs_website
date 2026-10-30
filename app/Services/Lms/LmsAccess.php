<?php

namespace App\Services\Lms;

use Modules\Estidama\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Role rules of the Estidama panel.
 *  - managers (super_admin, Admin, estidama_admin): everything
 *  - trainers (estidama_trainer): only the programs they instruct
 */
class LmsAccess
{
    public const MANAGER_ROLES = ['super_admin', 'Admin', 'estidama_admin'];
    public const TRAINER_ROLE = 'estidama_trainer';

    public static function isManager(?Authenticatable $user): bool
    {
        return $user instanceof User && $user->hasAnyRole(self::MANAGER_ROLES);
    }

    /** Trainer without a manager role (their queries are scoped). */
    public static function isTrainerOnly(?Authenticatable $user): bool
    {
        return $user instanceof User && ! self::isManager($user) && $user->hasRole(self::TRAINER_ROLE);
    }

    public static function isStaff(?Authenticatable $user): bool
    {
        return self::isManager($user) || ($user instanceof User && $user->hasRole(self::TRAINER_ROLE));
    }

    public static function canManageProgram(?Authenticatable $user, TrainingProgram|int|null $program): bool
    {
        if (self::isManager($user)) {
            return true;
        }
        if (! self::isTrainerOnly($user) || $program === null) {
            return false;
        }
        $instructorId = $program instanceof TrainingProgram
            ? $program->instructor_id
            : TrainingProgram::whereKey($program)->value('instructor_id');

        return $instructorId !== null && (string) $instructorId === (string) $user->getAuthIdentifier();
    }

    /** Ids of the programs a trainer instructs. */
    public static function trainerProgramIds(?Authenticatable $user): array
    {
        if (! $user) {
            return [];
        }

        return TrainingProgram::where('instructor_id', $user->getAuthIdentifier())->pluck('id')->all();
    }

    /** Restricts a query on a table with $column (program id) to the trainer's programs. */
    public static function scopeToTrainer(Builder $query, ?Authenticatable $user, string $column = 'training_program_id'): Builder
    {
        if (self::isTrainerOnly($user)) {
            $query->whereIn($column, self::trainerProgramIds($user) ?: [0]);
        }

        return $query;
    }
}
