<?php

namespace App\Filament\Resources\ServiceSubmissionResource\Pages;

use App\Filament\Resources\ServiceSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceSubmission extends EditRecord
{
    protected static string $resource = ServiceSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
