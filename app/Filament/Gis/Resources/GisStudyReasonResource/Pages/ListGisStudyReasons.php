<?php

namespace App\Filament\Gis\Resources\GisStudyReasonResource\Pages;

use App\Filament\Gis\Resources\GisStudyReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGisStudyReasons extends ListRecords
{
    protected static string $resource = GisStudyReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
