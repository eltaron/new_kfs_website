<?php

namespace Modules\Marketplace\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketOrderItem;
use Modules\Marketplace\Support\MarketplaceAccess;

/**
 * تقارير المتجر — sales per vendor, per category and per month for a date range.
 * Only confirmed orders (paid online / COD) and non-cancelled lines are counted.
 */
class MarketReports extends Page
{
    protected static ?string $slug = 'reports';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'الطلبات والمالية';

    protected static ?string $navigationLabel = 'التقارير';

    protected static ?string $title = 'تقارير المتجر';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'marketplace::filament.admin.reports';

    public ?string $from = null;

    public ?string $until = null;

    public static function canAccess(): bool
    {
        return MarketplaceAccess::isAdmin(auth()->user());
    }

    public function mount(): void
    {
        $this->from = now()->subMonths(5)->startOfMonth()->toDateString();
        $this->until = now()->toDateString();
    }

    private function range(): array
    {
        try {
            $from = Carbon::parse((string) $this->from)->startOfDay();
            $until = Carbon::parse((string) $this->until)->endOfDay();
        } catch (\Throwable) {
            $from = now()->subMonths(5)->startOfMonth();
            $until = now()->endOfDay();
        }

        return $from->lte($until) ? [$from, $until] : [$until->copy()->startOfDay(), $from->copy()->endOfDay()];
    }

    private function lines()
    {
        [$from, $until] = $this->range();

        return MarketOrderItem::query()
            ->join('market_orders', 'market_orders.id', '=', 'market_order_items.order_id')
            ->whereIn('market_orders.status', MarketOrder::FULFILLABLE)
            ->where('market_order_items.fulfillment_status', '!=', MarketOrderItem::FULFILLMENT_CANCELLED)
            ->whereBetween('market_orders.created_at', [$from, $until]);
    }

    public function getTotals(): array
    {
        $row = $this->lines()->selectRaw('COUNT(DISTINCT market_orders.id) as orders, COALESCE(SUM(market_order_items.quantity),0) as qty,
            COALESCE(SUM(market_order_items.line_total),0) as sales, COALESCE(SUM(market_order_items.commission_amount),0) as commission,
            COALESCE(SUM(market_order_items.vendor_earning),0) as earnings')->first();

        return [
            'orders' => (int) $row->orders,
            'qty' => (int) $row->qty,
            'sales' => (float) $row->sales,
            'commission' => (float) $row->commission,
            'earnings' => (float) $row->earnings,
        ];
    }

    public function getByVendor(): Collection
    {
        return $this->lines()
            ->join('market_vendors', 'market_vendors.id', '=', 'market_order_items.vendor_id')
            ->groupBy('market_vendors.id', 'market_vendors.store_name')
            ->selectRaw('market_vendors.store_name as name, COUNT(DISTINCT market_orders.id) as orders, SUM(market_order_items.quantity) as qty,
                SUM(market_order_items.line_total) as sales, SUM(market_order_items.commission_amount) as commission, SUM(market_order_items.vendor_earning) as earnings')
            ->orderByDesc('sales')
            ->get();
    }

    public function getByCategory(): Collection
    {
        return $this->lines()
            ->leftJoin('market_products', 'market_products.id', '=', 'market_order_items.product_id')
            ->leftJoin('market_categories', 'market_categories.id', '=', 'market_products.category_id')
            ->groupBy('market_categories.id', 'market_categories.name')
            ->selectRaw("COALESCE(market_categories.name, 'غير مصنف') as name, SUM(market_order_items.quantity) as qty, SUM(market_order_items.line_total) as sales")
            ->orderByDesc('sales')
            ->get();
    }

    public function getByMonth(): Collection
    {
        $month = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', market_orders.created_at)"
            : "DATE_FORMAT(market_orders.created_at, '%Y-%m')";

        return $this->lines()
            ->groupByRaw($month)
            ->selectRaw("{$month} as month, COUNT(DISTINCT market_orders.id) as orders, SUM(market_order_items.line_total) as sales, SUM(market_order_items.commission_amount) as commission")
            ->orderBy('month')
            ->get();
    }
}
