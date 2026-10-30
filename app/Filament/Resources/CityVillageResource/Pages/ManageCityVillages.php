<?php

namespace App\Filament\Resources\CityVillageResource\Pages;

use App\Filament\Resources\CityVillageResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCityVillages extends ManageRecords
{
    protected static string $resource = CityVillageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
