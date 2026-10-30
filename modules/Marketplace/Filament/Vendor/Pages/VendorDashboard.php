<?php

namespace Modules\Marketplace\Filament\Vendor\Pages;

use Filament\Pages\Dashboard;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Marketplace\Filament\Vendor\Widgets\VendorStatsWidget;
use Modules\Marketplace\Support\MarketplaceAccess;

class VendorDashboard extends Dashboard
{
    protected static ?string $slug = 'dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    public static function getNavigationLabel(): string
    {
        return 'الرئيسية';
    }

    public function getTitle(): string | Htmlable
    {
        $vendor = MarketplaceAccess::currentVendor();

        return $vendor ? 'أهلاً بك في متجر «'.$vendor->store_name.'»' : 'لوحة البائع';
    }

    public function getWidgets(): array
    {
        return [VendorStatsWidget::class];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }
}
