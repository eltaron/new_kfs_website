<?php

namespace App\Filament\Estidama\Resources\TrainingProgramResource\Pages;

use App\Filament\Estidama\Resources\TrainingProgramResource;
use App\Services\Lms\LmsAccess;
use Filament\Resources\Pages\CreateRecord;

class CreateTrainingProgram extends CreateRecord
{
    protected static string $resource = TrainingProgramResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // A trainer always owns the programs they create.
        if (LmsAccess::isTrainerOnly(auth()->user())) {
            $data['instructor_id'] = auth()->id();
            $data['is_paid'] = false;
            $data['price'] = 0;
        }
        if (empty($data['is_paid'])) {
            $data['price'] = 0;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
