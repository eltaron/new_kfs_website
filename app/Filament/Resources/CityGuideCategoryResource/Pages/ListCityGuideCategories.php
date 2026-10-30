<?php

namespace App\Filament\Resources\CityGuideCategoryResource\Pages;

use App\Filament\Resources\CityGuideCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCityGuideCategories extends ListRecords
{
    protected static string $resource = CityGuideCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
