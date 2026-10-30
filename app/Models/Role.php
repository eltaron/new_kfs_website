<?php

namespace App\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * مفتاح جدول roles يختلف حسب قاعدة البيانات:
 *  - قاعدة الإنتاج: varchar (UUID) — بلا قيمة افتراضية.
 *  - قاعدة مبنية من الـ migrations (والاختبارات): bigint auto-increment كما في spatie.
 *
 * لذلك نحدد نوع المفتاح من نوع العمود فعلياً. معاملة UUID كرقم كانت تحوّله إلى int
 * (مثلاً "5c8a…" → 5) فيُسند assignRole() دوراً خاطئاً بصمت.
 * (HasUuids ثابتة كانت تكسر الحالة الثانية: Data truncated for column 'id'.)
 */
class Role extends SpatieRole
{
    protected static ?bool $stringKeys = null;

    protected static function booted(): void
    {
        static::creating(function (Role $role) {
            if (static::usesStringKeys() && blank($role->getKey())) {
                $role->setAttribute($role->getKeyName(), (string) Str::uuid());
            }
        });
    }

    public static function usesStringKeys(): bool
    {
        if (static::$stringKeys === null) {
            try {
                $table = config('permission.table_names.roles', 'roles');
                static::$stringKeys = in_array(strtolower(Schema::getColumnType($table, 'id')), ['varchar', 'char', 'string', 'uuid'], true);
            } catch (\Throwable) {
                return false; // قاعدة غير متاحة بعد — لا نثبّت النتيجة
            }
        }

        return static::$stringKeys;
    }

    public function getIncrementing()
    {
        return ! static::usesStringKeys();
    }

    public function getKeyType()
    {
        return static::usesStringKeys() ? 'string' : 'int';
    }
}
