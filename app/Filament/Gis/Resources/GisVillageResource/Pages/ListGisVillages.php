<?php

namespace App\Filament\Gis\Resources\GisVillageResource\Pages;

use App\Filament\Gis\Resources\GisVillageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGisVillages extends ListRecords
{
    protected static string $resource = GisVillageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
