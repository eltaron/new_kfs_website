<?php

namespace App\Filament\Gis\Widgets;

use App\Filament\Gis\Pages\WorkflowDashboard;
use App\Filament\Gis\Resources\RemovalOrderResource;
use Filament\Widgets\Widget;
use Modules\RemovalGovernance\Models\RemovalOrder;

/**
 * GIS dashboard hero: greeting, KPIs and the removal-order workflow pipeline
 * (one card per stage with its count). Counts use the same per-role / per-center
 * scope as the removal-orders table (RemovalOrderResource::getEloquentQuery()).
 */
class RemovalWorkflowOverview extends Widget
{
    protected static string $view = 'filament.gis.widgets.removal-workflow-overview';

    protected static ?int $sort = -10;

    /** First thing on the dashboard: render with the page instead of a lazy placeholder. */
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /** Stage => tab key on the removal-orders list page. */
    private const TAB_KEYS = [
        RemovalOrder::STAGE_CREATED => 'new',
    ];

    /** Stage => heroicon. */
    private const ICONS = [
        RemovalOrder::STAGE_CREATED => 'heroicon-o-sparkles',
        RemovalOrder::STAGE_ENGINEERING_REVIEW => 'heroicon-o-pencil-square',
        RemovalOrder::STAGE_SPATIAL_PENDING => 'heroicon-o-cursor-arrow-rays',
        RemovalOrder::STAGE_SPATIAL_DIMENSIONED => 'heroicon-o-map',
        RemovalOrder::STAGE_PDF_READY => 'heroicon-o-building-office-2',
        RemovalOrder::STAGE_SYSTEMS_PROCESSING => 'heroicon-o-cog-6-tooth',
        RemovalOrder::STAGE_LOCAL_UNIT_APPROVAL => 'heroicon-o-check-badge',
        RemovalOrder::STAGE_GIS_PENDING => 'heroicon-o-arrow-up-on-square',
        RemovalOrder::STAGE_VISA_PENDING => 'heroicon-o-academic-cap',
        RemovalOrder::STAGE_COMPLETED => 'heroicon-o-check-circle',
    ];

    public static function canView(): bool
    {
        return RemovalOrderResource::canAccess() || WorkflowDashboard::canAccess();
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $legalDone = "(COALESCE(citizen_notification_file, '') <> '' AND COALESCE(police_notification_file, '') <> '' AND COALESCE(notification_to_the_revenue_department_file, '') <> '')";

        $rows = RemovalOrderResource::getEloquentQuery()
            ->selectRaw("stage, COUNT(*) as total, SUM(CASE WHEN {$legalDone} THEN 1 ELSE 0 END) as legal_done")
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        $count = fn (string $stage) => (int) ($rows[$stage]->total ?? 0);
        $total = (int) $rows->sum('total');
        $completed = $count(RemovalOrder::STAGE_COMPLETED);
        $legalDoneCount = (int) ($rows[RemovalOrder::STAGE_COMPLETED]->legal_done ?? 0);

        $canList = RemovalOrderResource::canAccess();
        $stageUrl = function (string $stage) use ($canList) {
            if ($canList) {
                return RemovalOrderResource::getUrl('index', ['activeTab' => self::TAB_KEYS[$stage] ?? $stage]);
            }

            return WorkflowDashboard::canAccess() ? WorkflowDashboard::getUrl() : null;
        };

        $stages = collect(RemovalOrder::stages())->map(fn ($label, $stage) => [
            'key' => $stage,
            'label' => $label,
            'count' => $count($stage),
            'icon' => self::ICONS[$stage] ?? 'heroicon-o-document-text',
            'url' => $stageUrl($stage),
        ])->values();

        $inProgressStages = $stages->reject(fn ($s) => $s['key'] === RemovalOrder::STAGE_COMPLETED);
        $busiest = $inProgressStages->sortByDesc('count')->first();

        $links = collect([
            ['label' => 'سير العمل', 'icon' => 'heroicon-o-arrow-path', 'url' => WorkflowDashboard::canAccess() ? WorkflowDashboard::getUrl() : null],
            ['label' => 'قرارات الإزالة', 'icon' => 'heroicon-o-document-text', 'url' => $canList ? RemovalOrderResource::getUrl('index') : null],
            ['label' => 'سجل التقارير', 'icon' => 'heroicon-o-clipboard-document-list', 'url' => \App\Filament\Gis\Pages\RemovalReportsPage::canAccess() ? \App\Filament\Gis\Pages\RemovalReportsPage::getUrl() : null],
        ])->filter(fn ($l) => $l['url'])->values();

        $hour = (int) now()->format('G');

        return [
            'greeting' => $hour < 12 ? 'صباح الخير' : 'مساء الخير',
            'name' => \Illuminate\Support\Str::words(trim((string) $user->name), 2, ''),
            'role' => $user->roles->pluck('name')->reject(fn ($r) => $r === 'super_admin')->first() ?? ($user->hasRole('super_admin') ? 'مدير النظام' : null),
            'center' => $user->center,
            'today' => now()->locale('ar')->translatedFormat('l j F Y'),
            'links' => $links,
            'kpis' => [
                ['label' => 'إجمالي القرارات', 'value' => $total, 'icon' => 'heroicon-o-rectangle-stack', 'tone' => 'primary'],
                ['label' => 'قيد التنفيذ', 'value' => $total - $completed, 'icon' => 'heroicon-o-arrow-path', 'tone' => 'info'],
                ['label' => 'بانتظار الإجراءات القانونية', 'value' => $completed - $legalDoneCount, 'icon' => 'heroicon-o-clock', 'tone' => 'warning',
                    'url' => $canList ? RemovalOrderResource::getUrl('index', ['activeTab' => 'completed_pending_legal']) : null],
                ['label' => 'منتهية بالكامل', 'value' => $legalDoneCount, 'icon' => 'heroicon-o-check-circle', 'tone' => 'success'],
            ],
            'stages' => $stages,
            'busiest' => ($busiest['count'] ?? 0) > 0 ? $busiest : null,
            'maxCount' => max(1, (int) $inProgressStages->max('count')),
        ];
    }
}
