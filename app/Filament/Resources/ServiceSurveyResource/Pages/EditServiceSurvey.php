<?php

namespace App\Filament\Resources\ServiceSurveyResource\Pages;

use App\Filament\Resources\ServiceSurveyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceSurvey extends EditRecord
{
    protected static string $resource = ServiceSurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
