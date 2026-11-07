<?php

namespace App\Filament\Gis\Resources\IncomingRemovalsResource\Pages;

use App\Filament\Gis\Resources\IncomingRemovalsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIncomingRemovals extends EditRecord
{
    protected static string $resource = IncomingRemovalsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
