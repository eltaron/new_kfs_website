<?php

namespace App\Filament\Resources\NationalProjectResource\Pages;

use App\Filament\Resources\NationalProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNationalProject extends EditRecord
{
    protected static string $resource = NationalProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
