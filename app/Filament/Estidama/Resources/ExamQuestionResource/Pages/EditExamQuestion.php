<?php

namespace App\Filament\Estidama\Resources\ExamQuestionResource\Pages;

use App\Filament\Estidama\Resources\ExamQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExamQuestion extends EditRecord
{
    protected static string $resource = ExamQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ExamQuestionResource::normalizeQuestionData($data);
    }
}
