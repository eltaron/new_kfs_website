<?php

namespace Modules\Syndicate\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Syndicate\Filament\PanelContext;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;

class SupervisionStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '120s';

    public static function canView(): bool
    {
        return PanelContext::userCanUsePanel();
    }

    protected function getStats(): array
    {
        $projects = Project::query()->selectRaw('work_status, count(*) c')->groupBy('work_status')->pluck('c', 'work_status');
        $openSupervision = SyndicateRequest::open()->where('type', SyndicateRequest::TYPE_SUPERVISION)->count();
        $openCancellation = SyndicateRequest::open()->where('type', SyndicateRequest::TYPE_CANCELLATION)->count();
        $issuedMonth = Certificate::where('kind', Certificate::KIND_SUPERVISION)->where('issued_at', '>=', now()->startOfMonth())->count();

        return [
            Stat::make('مشروعات تحت الإشراف', (string) ($projects[Project::WORK_ACTIVE] ?? 0))
                ->color('success')->icon('heroicon-o-shield-check'),
            Stat::make('موقوفة - بدون إشراف', (string) ($projects[Project::WORK_SUSPENDED] ?? 0))
                ->description('يجب إيقاف الأعمال بها')->color('danger')->icon('heroicon-o-hand-raised'),
            Stat::make('طلبات إشراف مفتوحة', (string) $openSupervision)
                ->description(PanelContext::isSyndicate() ? 'بانتظار إصدار الشهادة' : 'لدى النقابة')->color('warning')->icon('heroicon-o-inbox-arrow-down'),
            Stat::make('طلبات إلغاء مفتوحة', (string) $openCancellation)->color('danger')->icon('heroicon-o-user-minus'),
            Stat::make('شهادات إشراف هذا الشهر', (string) $issuedMonth)->color('info')->icon('heroicon-o-document-check'),
        ];
    }
}
