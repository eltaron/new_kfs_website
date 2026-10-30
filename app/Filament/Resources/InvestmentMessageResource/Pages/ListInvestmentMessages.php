<?php

namespace App\Filament\Resources\InvestmentMessageResource\Pages;

use App\Filament\Resources\InvestmentMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvestmentMessages extends ListRecords
{
    protected static string $resource = InvestmentMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
