<?php

namespace Modules\SiteValidity\Policies;

use App\Models\User;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvStatus;

/**
 * من يرى/يعدل طلبات صلاحية الموقع.
 *  - المواطن: طلباته فقط (المسارات العامة تعيد 404 لغير المالك).
 *  - المركز الجيومكاني ومسؤول الخدمة: كل الطلبات.
 *  - مهندس التنظيم: كل الطلبات بعد تحديد موعد المعاينة.
 *  - مدير التنظيم: كل الطلبات (للاطلاع) ويعتمد ما رفع المهندس ملفه النهائي.
 * التحولات نفسها تحكمها WorkflowService::can().
 */
class SiteValidityRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return SvAccess::isStaff($user) || SvAccess::isTech($user);
    }

    public function view(User $user, SiteValidityRequest $request): bool
    {
        if ($request->isOwnedBy($user) || SvAccess::isAdmin($user) || SvAccess::isGeo($user) || SvAccess::isDirector($user)) {
            return true;
        }
        if (SvAccess::isTech($user) && (string) $request->created_by === (string) $user->getKey()) {
            return true;
        }

        return SvAccess::isEngineer($user) && $request->status !== SvStatus::SUBMITTED;
    }

    /** المواطن من البوابة العامة؛ ومن اللوحة: المركز التكنولوجي (ومسؤول الخدمة) نيابةً عن المواطن. */
    public function create(User $user): bool
    {
        return SvAccess::canCreateRequests($user);
    }

    public function update(User $user, SiteValidityRequest $request): bool
    {
        return SvAccess::isAdmin($user);
    }

    public function delete(User $user, SiteValidityRequest $request): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, SiteValidityRequest $request): bool
    {
        return false;
    }

    public function restore(User $user, SiteValidityRequest $request): bool
    {
        return false;
    }

    public function replicate(User $user, SiteValidityRequest $request): bool
    {
        return false;
    }
}
