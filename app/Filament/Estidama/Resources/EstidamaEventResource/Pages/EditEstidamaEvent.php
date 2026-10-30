<?php

namespace App\Filament\Estidama\Resources\EstidamaEventResource\Pages;

use App\Filament\Estidama\Resources\EstidamaEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEstidamaEvent extends EditRecord
{
    protected static string $resource = EstidamaEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
