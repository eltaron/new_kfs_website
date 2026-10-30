<?php

namespace Modules\Marketplace\Filament\Admin\Resources\OrderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Marketplace\Filament\Admin\Resources\OrderResource;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
