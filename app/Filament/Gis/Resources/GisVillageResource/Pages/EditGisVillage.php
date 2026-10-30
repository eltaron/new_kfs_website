<?php

namespace App\Filament\Gis\Resources\GisVillageResource\Pages;

use App\Filament\Gis\Resources\GisVillageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGisVillage extends EditRecord
{
    protected static string $resource = GisVillageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
