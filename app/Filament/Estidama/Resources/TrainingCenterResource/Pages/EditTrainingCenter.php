<?php

namespace App\Filament\Estidama\Resources\TrainingCenterResource\Pages;

use App\Filament\Estidama\Resources\TrainingCenterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrainingCenter extends EditRecord
{
    protected static string $resource = TrainingCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
