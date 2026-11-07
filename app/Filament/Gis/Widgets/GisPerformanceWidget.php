<?php

namespace App\Filament\Gis\Widgets;

use Modules\Gis\Models\GisSubmission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GisPerformanceWidget extends BaseWidget
{
    // تحديث البيانات كل دقيقة (كان 15 ثانية: طلب كامل للخادم كل 15 ثانية لكل لوحة مفتوحة)
    protected static ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'Admin', 'مدير المركز', 'رؤوساء الاقسام', 'مدير الادارة الهندسية']);
    }

    protected function getStats(): array
    {
        return [
            Stat::make('إجمالي طلبات الخدمات', GisSubmission::count())
                ->description('إجمالي المعاملات المسجلة')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->chart([7, 2, 10, 3, 15, 4, 17]) // رسم توضيحي للنشاط
                ->color('info'),

            Stat::make('المحصلات المالية', number_format((float) \Modules\Payments\Models\Transaction::query()->completed()->gis()->sum('amount'), 2) . ' ج.م')
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
