<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * جدول permissions يستخدم مفتاحًا رقميًا (bigint auto-increment)، لذلك بدون HasUuids.
 */
class Permission extends SpatiePermission
{
}
