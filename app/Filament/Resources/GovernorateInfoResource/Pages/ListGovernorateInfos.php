<?php

namespace App\Filament\Resources\GovernorateInfoResource\Pages;

use App\Filament\Resources\GovernorateInfoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGovernorateInfos extends ListRecords
{
    protected static string $resource = GovernorateInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
