<?php

namespace App\Filament\Gis\Resources\GisShiakhaResource\Pages;

use App\Filament\Gis\Resources\GisShiakhaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGisShiakhas extends ListRecords
{
    protected static string $resource = GisShiakhaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
