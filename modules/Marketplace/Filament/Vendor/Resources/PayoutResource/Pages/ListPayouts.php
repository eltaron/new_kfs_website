<?php

namespace Modules\Marketplace\Filament\Vendor\Resources\PayoutResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Marketplace\Filament\Vendor\Resources\PayoutResource;
use Modules\Marketplace\Filament\Vendor\Widgets\VendorStatsWidget;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    protected function getHeaderWidgets(): array
    {
        return [VendorStatsWidget::class];
    }
}
