<?php

namespace App\Filament\Gis\Resources\PendingRemovalsResource\Pages;

use App\Filament\Gis\Resources\PendingRemovalsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPendingRemovals extends ListRecords
{
    protected static string $resource = PendingRemovalsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
