<?php

namespace Modules\Marketplace\Filament\Admin\Resources\OrderResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Marketplace\Filament\Admin\Resources\OrderResource;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return array_map(
            fn ($action) => $action->after(fn () => $this->record->refresh()),
            OrderResource::adminActions(),
        );
    }
}
