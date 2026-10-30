<?php

namespace App\Filament\Gis\Resources\NewGisSubmissionsResource\Pages;

use App\Filament\Gis\Resources\NewGisSubmissionsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNewGisSubmissions extends ListRecords
{
    protected static string $resource = NewGisSubmissionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
