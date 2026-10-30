<?php

namespace Modules\Syndicate\Authorization;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Syndicate\Filament\PanelContext;

/**
 * سياسة صلاحيات موحدة لنماذج منظومة الإشراف الهندسي (المشروعات، الطلبات، الشهادات، المهندسون).
 *
 * الصلاحيات هنا مبنية على الأدوار (config('syndicate.roles')) وليست على صلاحيات Shield،
 * لأن لوحتي /syndicate و /council لا تُدار بـ Filament Shield. القيود الدقيقة لكل لوحة
 * (من يسجل الطلبات، من يصدر الشهادات...) مطبقة في canCreate/canEdit داخل الموارد وفي SupervisionService.
 *
 * تُسجل صراحة عبر Gate::policy في SyndicateServiceProvider، لذا فهي تتقدم على أي سياسات
 * يولدها الأمر shield:generate داخل Modules\Syndicate\Policies.
 */
class SyndicateStaffPolicy
{
    use HandlesAuthorization;

    protected function isStaff(User $user): bool
    {
        if (PanelContext::id() !== null) {
            return PanelContext::userCanUsePanel();
        }

        return method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(array_unique(array_merge(PanelContext::syndicateRoles(), PanelContext::councilRoles())));
    }

    public function viewAny(User $user): bool { return $this->isStaff($user); }
    public function view(User $user, $model): bool { return $this->isStaff($user); }
    public function create(User $user): bool { return $this->isStaff($user); }
    public function update(User $user, $model): bool { return $this->isStaff($user); }
    public function delete(User $user, $model): bool { return $this->isStaff($user); }
    public function deleteAny(User $user): bool { return $this->isStaff($user); }

    // السجلات الرسمية لا تُحذف نهائياً ولا تُستنسخ.
    public function forceDelete(User $user, $model): bool { return false; }
    public function forceDeleteAny(User $user): bool { return false; }
    public function restore(User $user, $model): bool { return false; }
    public function restoreAny(User $user): bool { return false; }
    public function replicate(User $user, $model): bool { return false; }
    public function reorder(User $user): bool { return false; }
}
