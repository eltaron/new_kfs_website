<?php

namespace App\Filament\Resources\ServiceSubmissionResource\Pages;

use App\Filament\Resources\ServiceSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceSubmissions extends ListRecords
{
    protected static string $resource = ServiceSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
