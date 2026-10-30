<?php

namespace Modules\Marketplace\Filament\Vendor\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketOrderItem;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Services\PayoutService;
use Modules\Marketplace\Support\MarketplaceAccess;

/**
 * Earnings & activity summary of the signed-in vendor.
 */
class VendorStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $vendorId = MarketplaceAccess::currentVendorId();
        $s = PayoutService::summary($vendorId);

        $newOrders = MarketOrderItem::query()
            ->where('vendor_id', $vendorId)
            ->where('fulfillment_status', MarketOrderItem::FULFILLMENT_PENDING)
            ->whereHas('order', fn ($q) => $q->whereIn('status', MarketOrder::FULFILLABLE))
            ->count();

        $published = MarketProduct::query()->where('vendor_id', $vendorId)->where('status', MarketProduct::STATUS_PUBLISHED)->count();
        $pending = MarketProduct::query()->where('vendor_id', $vendorId)->where('status', MarketProduct::STATUS_PENDING)->count();

        $egp = fn (string $v) => number_format((float) $v, 2).' ج.م';

        return [
            Stat::make('طلبات جديدة تحتاج تجهيز', (string) $newOrders)
                ->icon('heroicon-o-shopping-cart')
                ->color($newOrders > 0 ? 'warning' : 'gray'),
            Stat::make('إجمالي المبيعات', $egp($s['sales']))
                ->description('من '.$s['orders'].' طلب')
                ->icon('heroicon-o-chart-bar'),
            Stat::make('صافي أرباحك', $egp($s['earnings']))
                ->description('بعد عمولة المتجر: '.$egp($s['commission']))
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
            Stat::make('متاح للتحويل', $egp($s['available']))
                ->description('قيد التحويل: '.$egp($s['pending_payouts']).' • تم تحويله: '.$egp($s['paid_out']))
                ->icon('heroicon-o-banknotes')
                ->color('primary'),
            Stat::make('منتجات منشورة', (string) $published)
                ->description($pending > 0 ? $pending.' بانتظار المراجعة' : 'لا توجد منتجات بانتظار المراجعة')
                ->icon('heroicon-o-cube'),
        ];
    }
}
