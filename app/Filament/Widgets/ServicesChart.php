<?php

namespace App\Filament\Widgets;

use Modules\Gis\Models\GisSubmission;
use Modules\Citizen\Models\ServiceSubmission;
use Modules\Estidama\Models\Enrollment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ServicesChart extends ChartWidget
{
    protected static ?int $sort = 5;
    protected static ?string $heading = 'إحصائيات الطلبات — آخر 7 أيام';
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->format('Y-m-d'));

        $gisData = $days->map(fn($day) =>
            GisSubmission::whereDate('created_at', $day)->count()
        );

        $serviceData = $days->map(fn($day) =>
            ServiceSubmission::whereDate('created_at', $day)->count()
        );

        $enrollmentData = $days->map(fn($day) =>
            Enrollment::whereDate('created_at', $day)->count()
        );

        return [
            'datasets' => [
                [
                    'label' => 'GIS',
                    'data' => $gisData->values()->toArray(),
                    'backgroundColor' => '#3498db',
                    'borderColor' => '#2980b9',
                ],
                [
                    'label' => 'خدمات عامة',
                    'data' => $serviceData->values()->toArray(),
                    'backgroundColor' => '#f39c12',
                    'borderColor' => '#e67e22',
                ],
                [
                    'label' => 'استدامة',
                    'data' => $enrollmentData->values()->toArray(),
                    'backgroundColor' => '#27ae60',
                    'borderColor' => '#2ecc71',
                ],
            ],
            'labels' => $days->map(fn($day) => now()->parse($day)->format('d/m'))->values()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
