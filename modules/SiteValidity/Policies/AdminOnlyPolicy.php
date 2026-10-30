<?php

namespace Modules\SiteValidity\Policies;

use App\Models\User;
use Modules\SiteValidity\Support\SvAccess;

/** القوائم المرجعية (أنواع الخدمة، بنود المعاينة، أنواع المرفقات): مسؤول الخدمة فقط. */
class AdminOnlyPolicy
{
    public function viewAny(User $user): bool { return SvAccess::isAdmin($user); }
    public function view(User $user, $model): bool { return SvAccess::isAdmin($user); }
    public function create(User $user): bool { return SvAccess::isAdmin($user); }
    public function update(User $user, $model): bool { return SvAccess::isAdmin($user); }
    public function delete(User $user, $model): bool { return SvAccess::isAdmin($user); }
    public function deleteAny(User $user): bool { return SvAccess::isAdmin($user); }
    public function forceDelete(User $user, $model): bool { return false; }
    public function forceDeleteAny(User $user): bool { return false; }
    public function restore(User $user, $model): bool { return false; }
    public function restoreAny(User $user): bool { return false; }
    public function replicate(User $user, $model): bool { return false; }
    public function reorder(User $user): bool { return SvAccess::isAdmin($user); }
}
