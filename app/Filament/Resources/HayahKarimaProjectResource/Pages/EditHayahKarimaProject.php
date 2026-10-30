<?php

namespace App\Filament\Resources\HayahKarimaProjectResource\Pages;

use App\Filament\Resources\HayahKarimaProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHayahKarimaProject extends EditRecord
{
    protected static string $resource = HayahKarimaProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
