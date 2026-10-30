<?php

namespace Modules\SiteValidity\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvStatus;

/** مؤشرات حسب الدور: مهامي الآن + المتأخر + (للمسؤول) الإيرادات وغير المدفوع. */
class SvStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $user = auth()->user();
        $q = fn () => SiteValidityRequest::query()->visibleTo($user);
        $count = fn (array $statuses) => $q()->whereIn('status', $statuses)->count();
        $url = fn (string $tab) => url('/site-validity/requests?activeTab=' . $tab);

        $stats = [];

        if (SvAccess::isAdmin($user) || SvAccess::isGeo($user)) {
            $stats[] = Stat::make('بانتظار تحديد موعد', $count([SvStatus::SUBMITTED]))
                ->description('طلبات جديدة')->descriptionIcon('heroicon-m-calendar-days')->color('warning')->url($url('appointment'));
            $stats[] = Stat::make('رفع المرفقات', $count([SvStatus::INSPECTED]))
                ->description('بعد اكتمال المعاينة')->descriptionIcon('heroicon-m-arrow-up-tray')->color('info')->url($url('attachments'));
        }
        if (SvAccess::isAdmin($user) || SvAccess::isEngineer($user)) {
            $stats[] = Stat::make('بانتظار الموافقة', $count([SvStatus::APPOINTMENT_SET]))
                ->description('موعد محدد — يحتاج اعتماد')->descriptionIcon('heroicon-m-check-badge')->color('info')->url($url('approval'));
            $stats[] = Stat::make('قرار المهندس', $count([SvStatus::ATTACHMENTS_UPLOADED, SvStatus::COMPLETION_SUBMITTED]))
                ->description('استيفاء أو انتهاء')->descriptionIcon('heroicon-m-scale')->color('warning')->url($url('decision'));
        }

        $stats[] = Stat::make('المعاينات', $count([SvStatus::APPROVED]))
            ->description('معتمدة وبانتظار المعاينة')->descriptionIcon('heroicon-m-map')->color('primary')->url($url('inspections'));
        $stats[] = Stat::make('مواعيد متأخرة', $q()->overdue()->count())
            ->description('تجاوزت موعدها دون اكتمال المعاينة')->descriptionIcon('heroicon-m-exclamation-triangle')->color('danger');
        $stats[] = Stat::make('استيفاء لدى المواطن', $count([SvStatus::COMPLETION_REQUESTED]))
            ->descriptionIcon('heroicon-m-arrow-uturn-left')->color('danger')->url($url('citizen'));
        $stats[] = Stat::make('منتهية', $count([SvStatus::COMPLETED]))
            ->descriptionIcon('heroicon-m-document-check')->color('success')->url($url('done'));

        if (SvAccess::isAdmin($user)) {
            $revenue = (float) SiteValidityRequest::query()->whereNotNull('paid_at')->sum('paid_amount');
            $stats[] = Stat::make('الإيرادات المحصلة', number_format($revenue, 2) . ' ج.م')
                ->description(SiteValidityRequest::query()->whereNotNull('paid_at')->count() . ' طلب مدفوع')
                ->descriptionIcon('heroicon-m-banknotes')->color('success');
            $stats[] = Stat::make('غير مدفوع', SiteValidityRequest::query()->whereNull('paid_at')->where('status', '!=', SvStatus::REJECTED)->count())
                ->description('إجمالي الطلبات: ' . SiteValidityRequest::query()->count())
                ->descriptionIcon('heroicon-m-credit-card')->color('gray');
        }

        return $stats;
    }
}
