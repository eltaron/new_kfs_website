<?php

namespace App\Filament\Gis\Resources\GisSubmissionResource\Pages;

use App\Filament\Gis\Resources\GisSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGisSubmission extends EditRecord
{
    protected static string $resource = GisSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
