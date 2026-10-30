<?php

namespace App\Filament\Gis\Resources\GisSubServiceResource\Pages;

use App\Filament\Gis\Resources\GisSubServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGisSubServices extends ListRecords
{
    protected static string $resource = GisSubServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
