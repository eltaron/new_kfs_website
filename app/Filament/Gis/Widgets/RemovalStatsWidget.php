<?php

namespace App\Filament\Gis\Widgets;

use Modules\RemovalGovernance\Models\RemovalOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RemovalStatsWidget extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole([
            'super_admin', 'Admin',
            'مدير المركز',
            'مدير الادارة الهندسية',
            'رؤوساء الاقسام',
            'مدير المتغيرات',
            'عضو المتغيرات',
        ]);
    }

    /**
     * الإخطارات الثلاثة: المواطن / الشرطة / تحصيل 1%
     */
    private const LEGAL_MISSING_CONDITION = "(citizen_notification_file IS NULL OR citizen_notification_file = ''
        OR police_notification_file IS NULL OR police_notification_file = ''
        OR notification_to_the_revenue_department_file IS NULL OR notification_to_the_revenue_department_file = '')";

    private const LEGAL_DONE_CONDITION = "(citizen_notification_file IS NOT NULL AND citizen_notification_file != ''
        AND police_notification_file IS NOT NULL AND police_notification_file != ''
        AND notification_to_the_revenue_department_file IS NOT NULL AND notification_to_the_revenue_department_file != '')";

    protected function getStats(): array
    {
        $user = auth()->user();

        // قيادات المركز يشوفوا إحصائيات مركزهم فقط ولو مفيش مركز -> صفر
        if ($user->hasAnyRole(['مدير الادارة الهندسية', 'مدير المتغيرات', 'عضو المتغيرات'])) {
            if (!$user->center) {
                return $this->zeroStats();
            }
            $base = RemovalOrder::where('center', $user->center);
        } else {
            $base = RemovalOrder::query();
        }

        return [
            Stat::make('قرارات الإزالة', (clone $base)->count())
                ->description('إجمالي القرارات المسجلة')
                ->descriptionIcon('heroicon-m-scale')
                ->color('gray'),

            Stat::make('بدون ترخيص', (clone $base)->where('violation_type', 'new_violation')->count())
                ->description('بناء عشوائي كلي')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),

            Stat::make('بانتظار الاجرائات القانونية', (clone $base)
                ->where('stage', RemovalOrder::STAGE_COMPLETED)
                ->whereRaw(static::LEGAL_MISSING_CONDITION)
                ->count())
                ->description('completed بدون اخطار المواطن / الشرطة / الـ 1%')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('منتهية', (clone $base)
                ->where('stage', RemovalOrder::STAGE_COMPLETED)
                ->whereRaw(static::LEGAL_DONE_CONDITION)
                ->count())
                ->description('completed ورفع الإخطارات الثلاثة')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }

    private function zeroStats(): array
    {
        return [
            Stat::make('قرارات الإزالة', 0)->description('لم يُسجل مركز لهذا الحساب')->descriptionIcon('heroicon-m-scale')->color('gray'),
            Stat::make('بدون ترخيص', 0)->description('بناء عشوائي كلي')->descriptionIcon('heroicon-m-exclamation-circle')->color('danger'),
            Stat::make('بانتظار الاجرائات القانونية', 0)->description('completed بدون اخطار المواطن / الشرطة / الـ 1%')->descriptionIcon('heroicon-m-clock')->color('warning'),
            Stat::make('منتهية', 0)->description('completed ورفع الإخطارات الثلاثة')->descriptionIcon('heroicon-m-check-badge')->color('success'),
        ];
    }
}