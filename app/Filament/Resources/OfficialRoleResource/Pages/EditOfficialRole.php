<?php

namespace App\Filament\Resources\OfficialRoleResource\Pages;

use App\Filament\Resources\OfficialRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfficialRole extends EditRecord
{
    protected static string $resource = OfficialRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
