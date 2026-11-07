<?php

namespace App\Filament\Gis\Resources\GisStudyReasonResource\Pages;

use App\Filament\Gis\Resources\GisStudyReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGisStudyReason extends EditRecord
{
    protected static string $resource = GisStudyReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
