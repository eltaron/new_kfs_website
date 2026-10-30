<?php

namespace App\Filament\Estidama\Concerns;

use Modules\Estidama\Models\Exam;
use Modules\Estidama\Models\TrainingProgram;
use App\Services\Lms\LmsAccess;
use Illuminate\Database\Eloquent\Model;

/**
 * Relation managers whose owner is a TrainingProgram (or an Exam of one):
 * managers and the program's own trainer may manage the related records.
 */
trait AuthorizesProgramRelation
{
    protected function can(string $action, ?Model $record = null): bool
    {
        $user = auth()->user();

        if (LmsAccess::isManager($user) || LmsAccess::canManageProgram($user, static::programIdOf($this->getOwnerRecord()))) {
            return static::trainerActionAllowed($action);
        }

        return parent::can($action, $record);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = auth()->user();

        if (LmsAccess::isManager($user) || LmsAccess::canManageProgram($user, static::programIdOf($ownerRecord))) {
            return true;
        }

        return parent::canViewForRecord($ownerRecord, $pageClass);
    }

    /** Relation managers may restrict actions (e.g. read-only lists). */
    protected static function trainerActionAllowed(string $action): bool
    {
        return true;
    }

    protected static function programIdOf(?Model $owner): ?int
    {
        return match (true) {
            $owner instanceof TrainingProgram => (int) $owner->getKey(),
            $owner instanceof Exam => $owner->training_program_id ? (int) $owner->training_program_id : null,
            default => null,
        };
    }
}
