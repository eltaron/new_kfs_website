<?php

namespace Modules\SiteValidity\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvStatus;

/** توزيع الطلبات على الحالات (مسؤول الخدمة). */
class StatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected static ?string $heading = 'الطلبات حسب الحالة';

    protected static ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return SvAccess::isAdmin(auth()->user());
    }

    protected function getData(): array
    {
        $counts = SiteValidityRequest::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $palette = ['primary' => '#657342', 'success' => '#3D7A50', 'warning' => '#D8B03E', 'danger' => '#A63A32', 'info' => '#147C92', 'gray' => '#929083'];

        return [
            'datasets' => [[
                'label' => 'عدد الطلبات',
                'data' => array_map(fn ($s) => (int) ($counts[$s] ?? 0), SvStatus::all()),
                'backgroundColor' => array_map(fn ($s) => $palette[SvStatus::color($s)] ?? '#929083', SvStatus::all()),
                'borderRadius' => 6,
            ]],
            'labels' => array_map(fn ($s) => SvStatus::label($s), SvStatus::all()),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['ticks' => ['precision' => 0]]]];
    }
}
