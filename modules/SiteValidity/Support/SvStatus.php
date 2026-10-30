<?php

namespace Modules\SiteValidity\Support;

/**
 * حالات طلب صلاحية الموقع. التسميات والألوان قابلة للتعديل من الإعدادات (status_labels / status_colors).
 */
final class SvStatus
{
    public const SUBMITTED = 'submitted';
    public const APPOINTMENT_SET = 'appointment_set';
    public const APPROVED = 'approved';
    public const INSPECTED = 'inspected';
    public const ATTACHMENTS_UPLOADED = 'attachments_uploaded';
    public const COMPLETION_REQUESTED = 'completion_requested';
    public const COMPLETION_SUBMITTED = 'completion_submitted';
    public const FINAL_UPLOADED = 'final_uploaded';
    public const COMPLETED = 'completed';
    public const REJECTED = 'rejected';

    public const LABELS = [
        self::SUBMITTED => 'جديد — بانتظار تحديد موعد المعاينة',
        self::APPOINTMENT_SET => 'تم تحديد الموعد — بانتظار موافقة مهندس التنظيم',
        self::APPROVED => 'معتمد — بانتظار المعاينة',
        self::INSPECTED => 'تمت المعاينة — بانتظار رفع المرفقات',
        self::ATTACHMENTS_UPLOADED => 'بانتظار قرار مهندس التنظيم',
        self::COMPLETION_REQUESTED => 'مطلوب استيفاء من المواطن',
        self::COMPLETION_SUBMITTED => 'تم الاستيفاء — بانتظار مراجعة المهندس',
        self::FINAL_UPLOADED => 'تم رفع الملف النهائي — بانتظار اعتماد مدير التنظيم',
        self::COMPLETED => 'منتهي — الملف النهائي متاح',
        self::REJECTED => 'مرفوض',
    ];

    /** ألوان Filament: primary | success | warning | danger | info | gray */
    public const COLORS = [
        self::SUBMITTED => 'warning',
        self::APPOINTMENT_SET => 'info',
        self::APPROVED => 'primary',
        self::INSPECTED => 'info',
        self::ATTACHMENTS_UPLOADED => 'warning',
        self::COMPLETION_REQUESTED => 'danger',
        self::COMPLETION_SUBMITTED => 'warning',
        self::FINAL_UPLOADED => 'info',
        self::COMPLETED => 'success',
        self::REJECTED => 'gray',
    ];

    /** شرح مبسط للمواطن: ما الذي يحدث الآن ومن ينتظر. */
    public const CITIZEN_HINTS = [
        self::SUBMITTED => 'طلبك قيد المراجعة لدى المركز الجيومكاني لتحديد موعد المعاينة الميدانية.',
        self::APPOINTMENT_SET => 'تم تحديد موعد المعاينة، وبانتظار اعتماد مهندس التنظيم.',
        self::APPROVED => 'تم اعتماد الطلب. يرجى التواجد بالموقع في موعد المعاينة.',
        self::INSPECTED => 'تمت المعاينة الميدانية، وجارٍ إعداد الرفع المساحي والمرفقات الفنية.',
        self::ATTACHMENTS_UPLOADED => 'الطلب لدى مهندس التنظيم للمراجعة النهائية.',
        self::COMPLETION_REQUESTED => 'مطلوب منك استيفاء بعض البيانات أو المستندات — راجع الملاحظات وأرسل الاستيفاء.',
        self::COMPLETION_SUBMITTED => 'تم إرسال الاستيفاء، وبانتظار مراجعة مهندس التنظيم.',
        self::FINAL_UPLOADED => 'تمت المراجعة الفنية، والطلب لدى مدير التنظيم للاعتماد النهائي.',
        self::COMPLETED => 'تم الانتهاء من الطلب ويمكنك تنزيل الملف النهائي.',
        self::REJECTED => 'تم رفض الطلب. راجع سبب الرفض أدناه.',
    ];

    /** ترتيب مراحل المسار الطبيعي (للمؤشر المرحلي عند المواطن). */
    public const STEPS = [
        self::SUBMITTED => 'تقديم الطلب',
        self::APPOINTMENT_SET => 'موعد المعاينة',
        self::APPROVED => 'اعتماد المهندس',
        self::INSPECTED => 'المعاينة',
        self::ATTACHMENTS_UPLOADED => 'الرفع المساحي',
        self::COMPLETED => 'الملف النهائي',
    ];

    /** @return list<string> */
    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    public static function label(?string $status): string
    {
        if ($status === null) {
            return '—';
        }

        return SvSettings::json('status_labels')[$status] ?? self::LABELS[$status] ?? $status;
    }

    public static function color(?string $status): string
    {
        $color = SvSettings::json('status_colors')[$status] ?? self::COLORS[$status] ?? 'gray';

        return in_array($color, ['primary', 'success', 'warning', 'danger', 'info', 'gray'], true) ? $color : 'gray';
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_combine(self::all(), array_map(fn ($s) => self::label($s), self::all()));
    }

    public static function hint(string $status): string
    {
        return self::CITIZEN_HINTS[$status] ?? '';
    }

    /** موضع الحالة على المؤشر المرحلي (0..5). */
    public static function stepIndex(string $status): int
    {
        return match ($status) {
            self::SUBMITTED => 0,
            self::APPOINTMENT_SET => 1,
            self::APPROVED => 2,
            self::INSPECTED => 3,
            self::ATTACHMENTS_UPLOADED, self::COMPLETION_REQUESTED, self::COMPLETION_SUBMITTED, self::FINAL_UPLOADED => 4,
            self::COMPLETED => 5,
            default => 0,
        };
    }

    /** لون شارة الواجهة العامة (kfs-badge--*). */
    public static function webTone(string $status): string
    {
        return match (self::color($status)) {
            'success' => 'success',
            'danger' => 'danger',
            'warning' => 'warning',
            'gray' => 'dark',
            'info' => 'olive',
            default => 'gold',
        };
    }
}
