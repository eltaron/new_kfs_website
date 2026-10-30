<?php

namespace App\Filament\Resources\HayahKarimaProjectResource\Pages;

use App\Filament\Resources\HayahKarimaProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHayahKarimaProjects extends ListRecords
{
    protected static string $resource = HayahKarimaProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
