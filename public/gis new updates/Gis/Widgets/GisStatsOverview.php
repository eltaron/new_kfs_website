<?php

namespace App\Filament\Gis\Widgets;

use App\Models\GisMarkaz;
use App\Models\GisVillage;
use App\Models\GisSubmission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GisStatsOverview extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'مدير المركز', 'مدير الادارة الهندسية']);
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

            Stat::make('تحصيلات اليوم', GisSubmission::where('payment_status', 'paid')->whereDate('updated_at', today())->sum('total_amount') . ' ج.م')
                ->description('إجمالي الرسوم المسددة اليوم')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
        ];
    }
}
