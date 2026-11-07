<?php

namespace App\Filament\Gis\Widgets;

use App\Models\GisSubmission;
use Filament\Widgets\ChartWidget;

class RequestsStatusChart extends ChartWidget
{
    protected static ?string $heading = 'تحليل حالة الطلبات الجيومكانية';

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'مدير المركز', 'مدير الادارة الهندسية']);
    }

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'الطلبات',
                    'data' => [
                        GisSubmission::where('status', 'completed')->count(),
                        GisSubmission::where('status', 'processing')->count(),
                        GisSubmission::where('status', 'rejected')->count(),
                        GisSubmission::where('status', 'received')->count(),
                    ],
                    'backgroundColor' => ['#27ae60', '#3498db', '#e74c3c', '#f1c40f'],
                ],
            ],
            'labels' => ['مكتملة', 'قيد المعاينة', 'مرفوضة', 'جديدة'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut'; // شكل الدائرة المفرغة "مودرن"
    }
}
