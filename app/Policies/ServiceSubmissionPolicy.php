<?php

namespace App\Policies;

use App\Models\User;
use Modules\Citizen\Models\ServiceSubmission;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServiceSubmissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_service::submission');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceSubmission $serviceSubmission): bool
    {
        return $user->can('view_service::submission');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_service::submission');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceSubmission $serviceSubmission): bool
    {
        return $user->can('update_service::submission');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceSubmission $serviceSubmission): bool
    {
        return $user->can('delete_service::submission');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_service::submission');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, ServiceSubmission $serviceSubmission): bool
    {
        return $user->can('force_delete_service::submission');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_service::submission');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, ServiceSubmission $serviceSubmission): bool
    {
        return $user->can('restore_service::submission');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_service::submission');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, ServiceSubmission $serviceSubmission): bool
    {
        return $user->can('replicate_service::submission');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_service::submission');
    }
}
