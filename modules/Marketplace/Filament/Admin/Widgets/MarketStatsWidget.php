<?php

namespace Modules\Marketplace\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketPayout;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Models\MarketVendor;

class MarketStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $confirmed = MarketOrder::query()->whereIn('status', MarketOrder::FULFILLABLE);
        $egp = fn ($v) => number_format((float) $v, 2).' ج.م';

        $pendingVendors = MarketVendor::query()->where('status', MarketVendor::STATUS_PENDING)->count();
        $pendingProducts = MarketProduct::query()->where('status', MarketProduct::STATUS_PENDING)->count();
        $needsReview = MarketOrder::query()->where('needs_review', true)->count();

        return [
            Stat::make('طلبات انضمام بائعين', (string) $pendingVendors)
                ->description('معتمد: '.MarketVendor::query()->approved()->count())
                ->icon('heroicon-o-user-plus')->color($pendingVendors ? 'warning' : 'gray'),
            Stat::make('منتجات بانتظار المراجعة', (string) $pendingProducts)
                ->description('منشور: '.MarketProduct::query()->where('status', MarketProduct::STATUS_PUBLISHED)->count())
                ->icon('heroicon-o-cube')->color($pendingProducts ? 'warning' : 'gray'),
            Stat::make('الطلبات المؤكدة', (string) (clone $confirmed)->count())
                ->description('اليوم: '.(clone $confirmed)->whereDate('created_at', today())->count())
                ->icon('heroicon-o-shopping-bag'),
            Stat::make('إجمالي المبيعات', $egp((clone $confirmed)->sum('total')))
                ->description('العمولات: '.$egp((clone $confirmed)->sum('commission_total')))
                ->icon('heroicon-o-chart-bar')->color('success'),
            Stat::make('تحويلات قيد التنفيذ', $egp(MarketPayout::query()->where('status', MarketPayout::STATUS_PENDING)->sum('amount')))
                ->icon('heroicon-o-banknotes'),
            Stat::make('طلبات تحتاج مراجعة', (string) $needsReview)
                ->description('دفع بعد نفاد المخزون أو إلغاء بعد الدفع')
                ->icon('heroicon-o-exclamation-triangle')->color($needsReview ? 'danger' : 'gray'),
        ];
    }
}
