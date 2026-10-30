<?php

namespace Modules\Marketplace\Filament\Admin\Pages;

use Filament\Pages\Dashboard;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Marketplace\Filament\Admin\Widgets\MarketStatsWidget;

class MarketDashboard extends Dashboard
{
    protected static ?string $slug = 'dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    public static function getNavigationLabel(): string
    {
        return 'الرئيسية';
    }

    public function getTitle(): string | Htmlable
    {
        return 'إدارة متجر ذوي الهمم';
    }

    public function getWidgets(): array
    {
        return [MarketStatsWidget::class];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }
}
