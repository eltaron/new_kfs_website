<?php

namespace App\Filament\Gis\Resources\GisShiakhaResource\Pages;

use App\Filament\Gis\Resources\GisShiakhaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGisShiakha extends EditRecord
{
    protected static string $resource = GisShiakhaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
