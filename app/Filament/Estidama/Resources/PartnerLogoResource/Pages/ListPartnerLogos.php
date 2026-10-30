<?php

namespace App\Filament\Estidama\Resources\PartnerLogoResource\Pages;

use App\Filament\Estidama\Resources\PartnerLogoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartnerLogos extends ListRecords
{
    protected static string $resource = PartnerLogoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
