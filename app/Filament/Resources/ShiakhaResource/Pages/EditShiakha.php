<?php

namespace App\Filament\Resources\ShiakhaResource\Pages;

use App\Filament\Resources\ShiakhaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShiakha extends EditRecord
{
    protected static string $resource = ShiakhaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
