<?php

namespace App\Filament\Resources\MarkazResource\Pages;

use App\Filament\Resources\MarkazResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarkaz extends EditRecord
{
    protected static string $resource = MarkazResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
