<?php

namespace Modules\Marketplace\Filament\Vendor\Resources\OrderItemResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Marketplace\Filament\Vendor\Resources\OrderItemResource;

class ViewOrderItem extends ViewRecord
{
    protected static string $resource = OrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            OrderItemResource::updateStatusAction(Actions\Action::make('update_status'))
                ->after(fn () => $this->record->refresh()),
        ];
    }
}
