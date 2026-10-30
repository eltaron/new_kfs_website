<?php

namespace Modules\SiteValidity\Support;

/** رسائل تحقق عربية عامة (المشروع لا يحتوي lang/ar/validation.php). */
final class ValidationMessages
{
    /** @return array<string, string> */
    public static function all(): array
    {
        return [
            'required' => 'حقل «:attribute» مطلوب.',
            'string' => 'حقل «:attribute» يجب أن يكون نصاً.',
            'integer' => 'قيمة «:attribute» غير صالحة.',
            'numeric' => 'حقل «:attribute» يجب أن يكون رقماً.',
            'min' => 'حقل «:attribute» قصير جداً (الحد الأدنى :min).',
            'max' => 'حقل «:attribute» أكبر من المسموح (الحد الأقصى :max).',
            'exists' => 'القيمة المختارة في «:attribute» غير صالحة.',
            'in' => 'القيمة المختارة في «:attribute» غير صالحة.',
            'regex' => 'صيغة «:attribute» غير صحيحة.',
            'not_regex' => '«:attribute» يحتوي على رموز غير مسموح بها.',
            'file' => '«:attribute» يجب أن يكون ملفاً.',
            'uploaded' => 'تعذر رفع «:attribute» — تأكد من حجم الملف.',
            'mimes' => 'نوع ملف «:attribute» غير مسموح (المسموح: :values).',
            'extensions' => 'امتداد ملف «:attribute» غير مسموح (المسموح: :values).',
            'accepted' => 'يجب الموافقة على «:attribute».',
            'array' => 'قيمة «:attribute» غير صالحة.',
            'between' => 'قيمة «:attribute» يجب أن تكون بين :min و :max.',
        ];
    }
}
