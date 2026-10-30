<?php

namespace App\Filament\Resources\GovernorateInfoResource\Pages;

use App\Filament\Resources\GovernorateInfoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGovernorateInfo extends EditRecord
{
    protected static string $resource = GovernorateInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
