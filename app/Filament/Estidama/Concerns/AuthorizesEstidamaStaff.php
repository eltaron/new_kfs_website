<?php

namespace App\Filament\Estidama\Concerns;

use App\Services\Lms\LmsAccess;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Resource authorization for the Estidama panel:
 *  - estidama managers (super_admin / Admin / estidama_admin) may do everything;
 *  - trainers get what the resource grants in trainerCan() (own programs only,
 *    the resource query is scoped in getEloquentQuery());
 *  - anyone else falls back to the Shield policy permissions.
 */
trait AuthorizesEstidamaStaff
{
    public static function can(string $action, ?Model $record = null): bool
    {
        $user = auth()->user();

        if (LmsAccess::isManager($user)) {
            return true;
        }
        if (LmsAccess::isTrainerOnly($user) && static::trainerCan($action, $record, $user)) {
            return true;
        }

        return parent::can($action, $record);
    }

    /** Override in a resource to grant trainers abilities. */
    protected static function trainerCan(string $action, ?Model $record, Authenticatable $user): bool
    {
        return false;
    }
}
