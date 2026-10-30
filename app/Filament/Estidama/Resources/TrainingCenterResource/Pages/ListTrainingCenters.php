<?php

namespace App\Filament\Estidama\Resources\TrainingCenterResource\Pages;

use App\Filament\Estidama\Resources\TrainingCenterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrainingCenters extends ListRecords
{
    protected static string $resource = TrainingCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
