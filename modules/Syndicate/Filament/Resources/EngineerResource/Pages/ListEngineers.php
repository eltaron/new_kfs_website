<?php

namespace Modules\Syndicate\Filament\Resources\EngineerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Syndicate\Filament\Resources\EngineerResource;

class ListEngineers extends ListRecords
{
    protected static string $resource = EngineerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('إضافة مهندس')];
    }
}
