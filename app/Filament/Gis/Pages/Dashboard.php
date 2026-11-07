<?php
namespace App\Filament\Gis\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use App\Filament\Gis\Widgets\GisWelcomeWidget;
use App\Filament\Gis\Widgets\GisPerformanceWidget;
use App\Filament\Gis\Widgets\GisStatsOverview;
use App\Filament\Gis\Widgets\RemovalStatsWidget;
use App\Filament\Gis\Widgets\ViolationChart;
use App\Filament\Gis\Widgets\RequestsStatusChart;
use App\Filament\Gis\Widgets\RemovalWorkflowOverview;

class Dashboard extends BaseDashboard
{
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole([
            'super_admin', 'Admin',
            'مدير المركز', 'مدير الادارة الهندسية',
            'مهندس التنظيم', 'مدير التنظيم',
            'فني التنظيم', 'مدير الوحدة الفرعية',
            'العضو الميداني',
            'مدخل البيانات بالوحدة الفرعية',
            'محللي النظم', 'الدعم الاداري',
            'رؤوساء الاقسام',
            'مدير المتغيرات', 'عضو المتغيرات',
            'أخصائي النظم', 'مكتب المحافظ',
            'النائب المختص بمتابعة المنظومة طبقا لكشف الحضور',
            'رئيس المركز والمدينة',
        ]);
    }

    /**
     * لوحة تحكم مخصصة لكل صلاحية:
     * - الإدارة الممتدة (super_admin / Admin / مدير المركز / رؤوساء الاقسام): كل الإحصائيات والرسوم
     * - قيادات المركز: إحصائيات ورسوم قرارات مركزهم فقط (مدير الادارة الهندسية / مدير المتغيرات / عضو المتغيرات)
     * - باقي الأدوار: ترحيب وروابط سريعة خاصة بدور كل منهم
     */
    public function getWidgets(): array
    {
        $user = auth()->user();
        if (!$user) {
            return [];
        }

        // الإدارة الممتدة: رؤوساء الأقسام ومدير المركز يشوفوا الإحصائيات كاملة
        if ($user->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام'])) {
            // لوحة حوكمة قرارات الإزالة فقط؛ إحصائيات طلبات الخدمات المكانية تظهر عند إتاحتها
            $gisRequests = \App\Support\EServices::gisServicesOpen();

            return array_values(array_filter([
                RemovalWorkflowOverview::class,
                RemovalStatsWidget::class,
                ViolationChart::class,
                $gisRequests ? GisPerformanceWidget::class : null,
                $gisRequests ? GisStatsOverview::class : null,
                $gisRequests ? RequestsStatusChart::class : null,
            ]));
        }

        // قيادات المركز: الإحصائيات والرسوم مقيدة بمركز كل منهم
        if ($user->hasAnyRole(['مدير الادارة الهندسية', 'مدير المتغيرات', 'عضو المتغيرات'])) {
            return [
                RemovalWorkflowOverview::class,
                RemovalStatsWidget::class,
                ViolationChart::class,
            ];
        }

        // باقي الأدوار: لوحة مسار القرارات لمن يتعامل مع القرارات، وإلا الترحيب والروابط السريعة
        return [
            RemovalWorkflowOverview::canView() ? RemovalWorkflowOverview::class : GisWelcomeWidget::class,
        ];
    }
}