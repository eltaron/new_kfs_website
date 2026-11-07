<?php

namespace App\Filament\Gis\Resources\IncomingRemovalsResource\Pages;

use App\Filament\Gis\Resources\IncomingRemovalsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIncomingRemovals extends ListRecords
{
    protected static string $resource = IncomingRemovalsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
