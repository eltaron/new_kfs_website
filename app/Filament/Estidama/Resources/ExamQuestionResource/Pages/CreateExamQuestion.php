<?php

namespace App\Filament\Estidama\Resources\ExamQuestionResource\Pages;

use App\Filament\Estidama\Resources\ExamQuestionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExamQuestion extends CreateRecord
{
    protected static string $resource = ExamQuestionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ExamQuestionResource::normalizeQuestionData($data);
    }
}
