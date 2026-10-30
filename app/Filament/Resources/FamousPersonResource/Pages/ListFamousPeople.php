<?php

namespace App\Filament\Resources\FamousPersonResource\Pages;

use App\Filament\Resources\FamousPersonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFamousPeople extends ListRecords
{
    protected static string $resource = FamousPersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
