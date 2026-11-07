<?php

namespace App\Filament\Gis\Resources\RemovalOrdersAdminResource\Pages;

use App\Filament\Gis\Resources\RemovalOrdersAdminResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRemovalOrderAdmin extends ViewRecord
{
    protected static string $resource = RemovalOrdersAdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('رجوع للقائمة')
                ->icon('heroicon-o-arrow-right')
                ->url(RemovalOrdersAdminResource::getUrl('index')),
        ];
    }
}