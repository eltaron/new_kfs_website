<?php

namespace App\Filament\Resources\FamousPersonResource\Pages;

use App\Filament\Resources\FamousPersonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFamousPerson extends EditRecord
{
    protected static string $resource = FamousPersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
