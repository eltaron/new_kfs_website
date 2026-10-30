<?php

namespace App\Filament\Resources\LandmarkResource\Pages;

use App\Filament\Resources\LandmarkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandmark extends EditRecord
{
    protected static string $resource = LandmarkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
