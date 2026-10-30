<?php

namespace App\Filament\Gis\Resources\PendingGisSubmissionsResource\Pages;

use App\Filament\Gis\Resources\PendingGisSubmissionsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPendingGisSubmissions extends EditRecord
{
    protected static string $resource = PendingGisSubmissionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
