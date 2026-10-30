<?php

namespace Modules\SiteValidity\Support;

use App\Models\User;

/**
 * أدوار خدمة «صلاحية الموقع» ومن يدخل لوحة /site-validity.
 *
 *   site_validity_admin  مسؤول الخدمة (كل الصلاحيات + الإعدادات)
 *   geo_center           المركز الجيومكاني (الموعد، المعاينة، المرفقات)
 *   planning_engineer    مهندس التنظيم (الموافقة، استمارة المعاينة، الاستيفاء، رفع الملف النهائي)
 *   planning_director    مدير التنظيم (اعتماد الصلاحية وإنهاء الطلب بعد رفع المهندس للملف النهائي، أو إعادته للمهندس)
 *   tech_center          المركز التكنولوجي (إدخال طلب جديد نيابةً عن المواطن فقط — يرى ما أدخله)
 *   super_admin          يدخل كل اللوحات ويعامل كمسؤول الخدمة
 */
final class SvAccess
{
    public const ADMIN = 'site_validity_admin';
    public const GEO = 'geo_center';
    public const ENGINEER = 'planning_engineer';
    public const DIRECTOR = 'planning_director';
    public const TECH = 'tech_center';
    public const SUPER = 'super_admin';

    public const ROLE_LABELS = [
        self::ADMIN => 'مسؤول خدمة صلاحية الموقع',
        self::GEO => 'المركز الجيومكاني',
        self::ENGINEER => 'مهندس التنظيم',
        self::DIRECTOR => 'مدير التنظيم',
        self::TECH => 'المركز التكنولوجي',
    ];

    /** @return list<string> */
    public static function serviceRoles(): array
    {
        return [self::ADMIN, self::GEO, self::ENGINEER, self::DIRECTOR, self::TECH];
    }

    /** المركز التكنولوجي: يدخل اللوحة ويضيف طلبات فقط (ليس من «موظفي المعالجة»). */
    public static function isTech(?User $user): bool
    {
        return $user !== null && $user->hasRole(self::TECH);
    }

    /** من يضيف طلباً جديداً من اللوحة. */
    public static function canCreateRequests(?User $user): bool
    {
        return self::isAdmin($user) || self::isTech($user);
    }

    public static function isAdmin(?User $user): bool
    {
        return $user !== null && $user->hasAnyRole([self::ADMIN, self::SUPER]);
    }

    public static function isGeo(?User $user): bool
    {
        return $user !== null && $user->hasRole(self::GEO);
    }

    public static function isEngineer(?User $user): bool
    {
        return $user !== null && $user->hasRole(self::ENGINEER);
    }

    public static function isDirector(?User $user): bool
    {
        return $user !== null && $user->hasRole(self::DIRECTOR);
    }

    public static function isStaff(?User $user): bool
    {
        return $user !== null && $user->hasAnyRole([self::ADMIN, self::SUPER, self::GEO, self::ENGINEER, self::DIRECTOR]);
    }

    public static function canAccessPanel(User $user): bool
    {
        if (in_array(strtolower((string) $user->status), ['rejected', 'inactive', 'suspended', 'blocked', 'disabled'], true)) {
            return false;
        }

        return self::isStaff($user) || self::isTech($user);
    }

    /** وصف الدور الأساسي للعرض في الخط الزمني. */
    public static function roleKey(?User $user): ?string
    {
        return match (true) {
            $user === null => null,
            self::isAdmin($user) => self::ADMIN,
            self::isDirector($user) => self::DIRECTOR,
            self::isEngineer($user) => self::ENGINEER,
            self::isGeo($user) => self::GEO,
            self::isTech($user) => self::TECH,
            default => 'citizen',
        };
    }

    public static function roleLabel(?string $role): string
    {
        return match ($role) {
            null, '' => 'النظام',
            'citizen' => 'المواطن',
            'system' => 'النظام',
            default => self::ROLE_LABELS[$role] ?? $role,
        };
    }
}
