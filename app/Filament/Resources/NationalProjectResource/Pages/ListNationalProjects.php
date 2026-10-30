<?php

namespace App\Filament\Resources\NationalProjectResource\Pages;

use App\Filament\Resources\NationalProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNationalProjects extends ListRecords
{
    protected static string $resource = NationalProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
