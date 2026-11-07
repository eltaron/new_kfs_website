<?php

namespace App\Filament\Gis\Resources\GisMarkazResource\Pages;

use App\Filament\Gis\Resources\GisMarkazResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGisMarkazs extends ListRecords
{
    protected static string $resource = GisMarkazResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
