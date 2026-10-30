<?php

namespace App\Filament\Gis\Widgets;

use Modules\Gis\Models\GisMarkaz;
use Modules\Gis\Models\GisVillage;
use Modules\Gis\Models\GisSubmission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GisStatsOverview extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام']);
    }

    protected function getStats(): array
    {
        return [
            Stat::make('إجمالي المراكز', GisMarkaz::count())
                ->description('المراكز الإدارية المسجلة')
                ->descriptionIcon('heroicon-m-map')
                ->color('info'),

            Stat::make('القرى والعزب', GisVillage::count())
                ->description('التغطية الريفية الجغرافية')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color('success'),

            Stat::make('طلبات جديدة', GisSubmission::where('status', 'received')->count())
                ->description('بانتظار المراجعة الإدارية')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->chart([7, 3, 4, 5, 6, 3, 5, 8]) // رسم توضيحي بسيط
                ->color('warning'),

            Stat::make('تحصيلات اليوم', number_format((float) \Modules\Payments\Models\Transaction::query()->completed()->gis()->whereDate('completed_at', today())->sum('amount'), 2) . ' ج.م')
                ->description('إجمالي الرسوم المسددة اليوم')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
        ];
    }
}
