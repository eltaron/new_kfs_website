<?php

namespace App\Filament\Resources\ShiakhaResource\Pages;

use App\Filament\Resources\ShiakhaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShiakhas extends ListRecords
{
    protected static string $resource = ShiakhaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
