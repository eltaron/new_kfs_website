<?php

namespace App\Filament\Estidama\Resources\TrainingProgramResource\Pages;

use App\Filament\Estidama\Resources\TrainingProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrainingProgram extends EditRecord
{
    protected static string $resource = TrainingProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('public')
                ->label('معاينة صفحة البرنامج')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn () => route('estidama.program.show', $this->getRecord()), shouldOpenInNewTab: true),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('is_paid', $data) && empty($data['is_paid'])) {
            $data['price'] = 0;
        }

        return $data;
    }
}
