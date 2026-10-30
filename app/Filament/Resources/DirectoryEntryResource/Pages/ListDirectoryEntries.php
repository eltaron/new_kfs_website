<?php

namespace App\Filament\Resources\DirectoryEntryResource\Pages;

use App\Filament\Resources\DirectoryEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDirectoryEntries extends ListRecords
{
    protected static string $resource = DirectoryEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
