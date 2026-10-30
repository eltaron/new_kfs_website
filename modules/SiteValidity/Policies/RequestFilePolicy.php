<?php

namespace Modules\SiteValidity\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\SiteValidity\Models\RequestFile;

/**
 * تنزيل ملف: الموظفون الذين يرون الطلب يرون كل ملفاته،
 * والمواطن صاحب الطلب يرى ملفاته وملفات الاستيفاء والملف النهائي فقط (ليس المرفقات الفنية الداخلية).
 */
class RequestFilePolicy
{
    public function view(User $user, RequestFile $file): bool
    {
        $request = $file->request;
        if ($request === null || ! Gate::forUser($user)->allows('view', $request)) {
            return false;
        }

        // المواطن والمركز التكنولوجي: مستندات المواطن والاستيفاء والملف النهائي فقط (لا المرفقات الفنية الداخلية)
        if (! \Modules\SiteValidity\Support\SvAccess::isStaff($user)) {
            return in_array($file->kind, RequestFile::CITIZEN_VISIBLE, true)
                && ($file->kind !== RequestFile::KIND_FINAL || $request->finalReleased());
        }

        return true;
    }
}
