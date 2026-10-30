<?php

namespace App\Filament\Estidama\Resources\EstidamaEventResource\Pages;

use App\Filament\Estidama\Resources\EstidamaEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEstidamaEvents extends ListRecords
{
    protected static string $resource = EstidamaEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
