<?php

namespace App\Filament\Estidama\Widgets;

use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\TrainingProgram;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProgramPerformanceStats extends BaseWidget
{
    public static function canView(): bool
    {
        return \App\Services\Lms\LmsAccess::isManager(auth()->user());
    }

    protected function getStats(): array
    {
        $totalPrograms = TrainingProgram::count();
        $totalEnrollments = Enrollment::count();
        $completedEnrollments = Enrollment::where('status', 'completed')->count();
        $completionRate = $totalEnrollments > 0 ? round(($completedEnrollments / $totalEnrollments) * 100, 2) : 0;

        return [
            Stat::make('إجمالي البرامج', $totalPrograms)->description('العدد الكلي للبرامج'),
            Stat::make('إجمالي المسجلين', $totalEnrollments)->description('في جميع البرامج'),
            Stat::make('نسبة الإكمال العامة', $completionRate . '%')
                ->description('نسبة إتمام البرامج')
                ->color($completionRate >= 50 ? 'success' : 'warning'),
        ];
    }
}
