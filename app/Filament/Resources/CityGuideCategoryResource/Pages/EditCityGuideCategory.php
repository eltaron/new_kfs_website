<?php

namespace App\Filament\Resources\CityGuideCategoryResource\Pages;

use App\Filament\Resources\CityGuideCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCityGuideCategory extends EditRecord
{
    protected static string $resource = CityGuideCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
