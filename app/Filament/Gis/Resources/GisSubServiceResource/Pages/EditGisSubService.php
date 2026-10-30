<?php

namespace App\Filament\Gis\Resources\GisSubServiceResource\Pages;

use App\Filament\Gis\Resources\GisSubServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGisSubService extends EditRecord
{
    protected static string $resource = GisSubServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
