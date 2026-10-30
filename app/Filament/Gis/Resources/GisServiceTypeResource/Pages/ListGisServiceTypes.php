<?php

namespace App\Filament\Gis\Resources\GisServiceTypeResource\Pages;

use App\Filament\Gis\Resources\GisServiceTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGisServiceTypes extends ListRecords
{
    protected static string $resource = GisServiceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
