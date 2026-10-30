<?php

namespace App\Filament\Gis\Widgets;

use Modules\RemovalGovernance\Models\RemovalOrder;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ViolationChart extends ChartWidget
{
    protected static ?string $heading = 'توزيع مخالفات البناء حسب المراكز الإدارية';

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

    // تحديد لون الرسم البياني (ذهبي وكحلي)
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $user = auth()->user();

        // قيادات المركز -> رسم بياني لمركزهم فقط (ولو مفيش مركز -> بيانات فارغة)
        if ($user->hasAnyRole(['مدير الادارة الهندسية', 'مدير المتغيرات', 'عضو المتغيرات'])) {
            if (!$user->center) {
                return ['datasets' => [['label' => 'عدد القرارات', 'data' => [], 'backgroundColor' => [], 'borderColor' => 'transparent']], 'labels' => []];
            }
            $data = RemovalOrder::where('center', $user->center)
                ->select('center', DB::raw('count(*) as total'))
                ->groupBy('center')
                ->orderBy('total', 'desc')
                ->pluck('total', 'center')
                ->toArray();
        } else {
            // جلب البيانات: تجميع عدد المخالفات لكل مركز
            $data = RemovalOrder::select('center', DB::raw('count(*) as total'))
                ->groupBy('center')
                ->orderBy('total', 'desc')
                ->pluck('total', 'center')
                ->toArray();
        }

        return [
            'datasets' => [
                [
                    'label' => 'عدد القرارات',
                    'data' => array_values($data),
                    'backgroundColor' => [
                        '#e1b12c', // ذهبي
                        '#1e272e', // كحلي
                        '#2f3542',
                        '#718093',
                        '#27ae60'
                    ],
                    'borderColor' => 'transparent',
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // استخدام الـ Bar Chart لسهولة المقارنة بين المراكز
    }

    /**
     * تخصيص خيارات الرسم البياني (دعم الـ Dark Mode)
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
