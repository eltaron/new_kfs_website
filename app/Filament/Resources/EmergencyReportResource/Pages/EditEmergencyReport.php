<?php

namespace App\Filament\Resources\EmergencyReportResource\Pages;

use App\Filament\Resources\EmergencyReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmergencyReport extends EditRecord
{
    protected static string $resource = EmergencyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
