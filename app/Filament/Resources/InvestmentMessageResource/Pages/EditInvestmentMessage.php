<?php

namespace App\Filament\Resources\InvestmentMessageResource\Pages;

use App\Filament\Resources\InvestmentMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvestmentMessage extends EditRecord
{
    protected static string $resource = InvestmentMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
