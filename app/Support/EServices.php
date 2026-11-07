<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Modules\Content\Models\Setting;

/**
 * الخدمات الإلكترونية الثابتة (كروت الصفحة الرئيسية + قائمة الهيدر) التي
 * يمكن إخفاؤها من «الإعدادات العامة ← الخدمات الإلكترونية» في لوحة التحكم.
 *
 * الإعداد يُخزَّن في جدول settings بالمفتاح hidden_eservices كمصفوفة JSON
 * من مفاتيح SERVICES. الإخفاء يخص الروابط الظاهرة فقط ولا يعطّل المسارات.
 */
class EServices
{
    public const SETTING_KEY = 'hidden_eservices';

    /** «إتاحة التقديم على الخدمات المكانية» — off (default) shows the coming-soon page. */
    public const GIS_OPEN_KEY = 'gis_services_open';

    /** key => [label, route names that belong to this service] */
    public const SERVICES = [
        'gis' => ['الخدمات الجغرافية', ['gis.index']],
        'site_validity' => ['طلب صلاحية الموقع', ['site-validity.landing']],
        'engineering_supervision' => ['الإشراف الهندسي', ['citizen-lookup', 'syndicate.verify.form']],
        'exam_results' => ['نتائج الامتحانات', ['exam-results.index']],
        'emergency' => ['بلاغ طوارئ', ['emergency.create']],
        'estidama' => ['برنامج استدامة', ['estidama.index']],
        'market' => ['متجر ذوي الهمم', ['market.home']],
        'tour_booking' => ['حجز رحلة سياحية', ['tour-bookings.create', 'tour-bookings.index']],
        'directory' => ['دليل الهاتف', ['directory.index']],
        'advertising_guide' => ['دليل الإعلانات', ['advertising.guide']],
        'suggestions' => ['تقديم مقترح', ['suggestions.create']],
        'service_survey' => ['تقييم الخدمات', ['surveys.service.create']],
    ];

    private static ?array $hiddenRoutes = null;

    /** @return array<string,string> key => label (for the admin checklist) */
    public static function options(): array
    {
        return array_map(fn ($s) => $s[0], self::SERVICES);
    }

    /** Are GIS-portal service pages open to citizens? Closed unless an admin enables it. */
    public static function gisServicesOpen(): bool
    {
        $settings = Cache::rememberForever('site_settings', fn () => Setting::pluck('value', 'key'));

        return in_array($settings[self::GIS_OPEN_KEY] ?? null, ['1', 1, true, 'true'], true);
    }

    /** @return string[] hidden service keys */
    public static function hiddenKeys(): array
    {
        $settings = Cache::rememberForever('site_settings', fn () => Setting::pluck('value', 'key'));

        return self::decode($settings[self::SETTING_KEY] ?? null);
    }

    public static function isRouteHidden(string $route): bool
    {
        if (self::$hiddenRoutes === null) {
            self::$hiddenRoutes = [];
            foreach (self::hiddenKeys() as $key) {
                foreach (self::SERVICES[$key][1] ?? [] as $name) {
                    self::$hiddenRoutes[$name] = true;
                }
            }
        }

        return isset(self::$hiddenRoutes[$route]);
    }

    /** الرابط موجود وغير مخفي من لوحة التحكم */
    public static function visible(string $route): bool
    {
        return Route::has($route) && ! self::isRouteHidden($route);
    }

    public static function decode(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    /** Reset the per-request memo (after saving settings / in tests). */
    public static function flush(): void
    {
        self::$hiddenRoutes = null;
    }
}
