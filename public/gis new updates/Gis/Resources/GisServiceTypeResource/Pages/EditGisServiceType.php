<?php

namespace App\Filament\Gis\Resources\GisServiceTypeResource\Pages;

use App\Filament\Gis\Resources\GisServiceTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGisServiceType extends EditRecord
{
    protected static string $resource = GisServiceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
