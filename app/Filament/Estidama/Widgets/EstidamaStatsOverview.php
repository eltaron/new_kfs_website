<?php

namespace App\Filament\Estidama\Widgets;

use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\ExamSubmission;
use Modules\Estidama\Models\TrainingApplication;
use Modules\Estidama\Models\TrainingCertificate;
use Modules\Estidama\Models\TrainingProgram;
use App\Services\Lms\LmsAccess;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Headline numbers (scoped to the trainer's own programs for trainers). */
class EstidamaStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        return LmsAccess::isStaff(auth()->user());
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $scope = fn ($query, string $column = 'training_program_id') => LmsAccess::scopeToTrainer($query, $user, $column);

        $pendingApplications = $scope(TrainingApplication::query())->where('status', 'pending')->count();
        $openPrograms = $scope(TrainingProgram::query(), 'id')->where('status', 'open')->count();
        $activeLearners = $scope(Enrollment::query())->where('status', 'approved')->count();
        $completed = $scope(Enrollment::query())->where('status', 'completed')->count();
        $learning = $activeLearners + $completed;
        $completionRate = $learning ? round($completed / $learning * 100) : 0;
        $certificates = $scope(TrainingCertificate::query())->whereNull('revoked_at')->count();
        $needsGrading = ExamSubmission::query()
            ->where('status', ExamSubmission::STATUS_NEEDS_GRADING)
            ->whereHas('exam', fn ($q) => $scope($q))
            ->count();

        return [
            Stat::make('طلبات التحاق جديدة', $pendingApplications)
                ->description('بانتظار المراجعة')
                ->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->color($pendingApplications > 0 ? 'danger' : 'success'),

            Stat::make('برامج مفتوحة للتسجيل', $openPrograms)
                ->description('متاحة للمواطنين الآن')
                ->descriptionIcon('heroicon-m-lock-open')
                ->color('primary'),

            Stat::make('متدربون نشطون', $activeLearners)
                ->description('يتعلمون حاليًا')
                ->descriptionIcon('heroicon-m-play-circle')
                ->color('info'),

            Stat::make('نسبة الإتمام', $completionRate . '%')
                ->description($completed . ' متدرب أتمّوا برامجهم')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($completionRate >= 50 ? 'success' : 'warning'),

            Stat::make('شهادات صادرة', $certificates)
                ->description('شهادات إلكترونية قابلة للتحقق')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('success'),

            Stat::make('محاولات بانتظار التصحيح', $needsGrading)
                ->description('أسئلة مقالية تحتاج تقييم المدرب')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color($needsGrading > 0 ? 'warning' : 'gray'),
        ];
    }
}
