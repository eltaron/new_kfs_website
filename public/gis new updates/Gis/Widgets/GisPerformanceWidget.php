<?php

namespace App\Filament\Gis\Widgets;

use App\Models\GisSubmission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GisPerformanceWidget extends BaseWidget
{
    // تحديث البيانات كل 15 ثانية تلقائياً
    protected static ?string $pollingInterval = '15s';

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'مدير المركز', 'مدير الادارة الهندسية']);
    }

    protected function getStats(): array
    {
        return [
            Stat::make('إجمالي طلبات الخدمات', GisSubmission::count())
                ->description('إجمالي المعاملات المسجلة')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->chart([7, 2, 10, 3, 15, 4, 17]) // رسم توضيحي للنشاط
                ->color('info'),

            Stat::make('المحصلات المالية', number_format(GisSubmission::where('payment_status', 'paid')->sum('total_amount'), 2) . ' ج.م')
                ->description('رسوم الخدمات المسددة بالكامل')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('طلبات بانتظار المراجعة', GisSubmission::where('status', 'received')->count())
                ->description('تحتاج لتدقيق فني عاجل')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
