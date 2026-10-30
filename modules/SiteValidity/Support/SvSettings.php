<?php

namespace Modules\SiteValidity\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\SiteValidity\Models\Setting;

/**
 * إعدادات الخدمة القابلة للتعديل من لوحة /site-validity (صفحة «إعدادات الخدمة»).
 * القيم المخزنة في sv_settings تتقدم على القيم الافتراضية أدناه.
 */
final class SvSettings
{
    private const CACHE_KEY = 'sitevalidity.settings';

    public const DEFAULTS = [
        'page_title' => 'طلب صلاحية الموقع',
        'page_kicker' => 'الخدمات الإلكترونية — التخطيط العمراني',
        'page_intro' => 'خدمة إلكترونية تتيح لك التقدم بطلب شهادة صلاحية الموقع للبناء أو الترخيص، ومتابعة المعاينة الميدانية والرفع المساحي حتى استلام الملف النهائي — دون الحاجة لزيارة المركز إلا يوم المعاينة.',
        'instructions' => "سجّل الدخول بحسابك على البوابة (أو أنشئ حساباً جديداً).\nاختر نوع الخدمة واستكمل بيانات الرقم القومي والموقع (المركز ثم المدينة أو القرية).\nارفع الكروكي وصورة عقد الملكية وصورة البطاقة بصيغة PDF أو صورة واضحة.\nراجع البيانات والرسوم ثم أرسل الطلب واحتفظ برقمه.\nسيحدد المركز الجيومكاني موعد المعاينة ويظهر لك في «طلباتي» مع إشعار.\nبعد المعاينة والرفع المساحي يراجع مهندس التنظيم الطلب، وقد يطلب استيفاء بعض المستندات.\nعند الانتهاء يمكنك تنزيل الملف النهائي من صفحة الطلب.",
        'required_documents' => "كروكي الموقع (رسم توضيحي للموقع وحدوده)\nصورة عقد الملكية أو ما يثبت الحيازة\nصورة بطاقة الرقم القومي (الوجهان)",
        'cost_note' => 'الرسوم تُسدد إلكترونياً عبر منظومة الدفع الحكومي e-Finance (بطاقات بنكية / ميزة / فوري). تظهر قيمة الرسوم قبل إرسال الطلب.',
        'success_message' => 'تم استلام طلبك بنجاح. سيقوم المركز الجيومكاني بتحديد موعد المعاينة وإخطارك.',
        'default_fee' => '500',
        'payment_required' => '0',
        'enforce_appointment_time' => '1',
        'notify_mail' => '1',
        'status_labels' => '',
        'status_colors' => '',
    ];

    /** @return array<string, string|null> */
    public static function all(): array
    {
        // تُقرأ مرة واحدة لكل طلب: الدالة تُستدعى عشرات المرات في الصفحة الواحدة (التسميات والألوان…)،
        // وكل قراءة من كاش قاعدة البيانات = استعلام SQL.
        return once(function () {
            $stored = Cache::remember(self::CACHE_KEY, 300, function () {
                try {
                    return Schema::hasTable('sv_settings') ? Setting::query()->pluck('value', 'key')->all() : [];
                } catch (\Throwable) {
                    return [];
                }
            });

            return array_merge(self::DEFAULTS, array_filter($stored, fn ($v) => $v !== null));
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        return $all[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], [
            'value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : ($value === null ? null : (string) $value),
        ]);
        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        \Illuminate\Support\Once::flush();
    }

    public static function bool(string $key): bool
    {
        return filter_var(self::get($key, '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function paymentRequired(): bool
    {
        return self::bool('payment_required');
    }

    public static function enforceAppointmentTime(): bool
    {
        return self::bool('enforce_appointment_time');
    }

    public static function defaultFee(): string
    {
        $fee = self::get('default_fee');

        return number_format(max(0, (float) ($fee !== null && $fee !== '' ? $fee : config('site-validity.default_fee', 500))), 2, '.', '');
    }

    /** @return list<string> سطور نصية غير فارغة (التعليمات / المستندات المطلوبة). */
    public static function lines(string $key): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) self::get($key, '')) ?: [])));
    }

    /** @return array<string, string> */
    public static function json(string $key): array
    {
        $decoded = json_decode((string) self::get($key, ''), true);

        return is_array($decoded) ? array_filter($decoded, fn ($v) => is_string($v) && $v !== '') : [];
    }
}
