<?php

namespace App\Filament\Estidama\Resources\TrainingApplicationResource\Pages;

use App\Filament\Estidama\Resources\TrainingApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrainingApplication extends EditRecord
{
    protected static string $resource = TrainingApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) !== $this->getRecord()->status) {
            $data['reviewed_at'] = now();
            $data['reviewed_by'] = auth()->id();
        }
        if (($data['status'] ?? null) !== 'rejected') {
            $data['rejection_reason'] = null;
        }

        return $data;
    }
}
