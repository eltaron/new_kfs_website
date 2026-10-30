<?php

namespace Modules\SiteValidity\Filament\Resources\ServiceTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Modules\SiteValidity\Filament\Resources\ServiceTypeResource;

class ManageServiceTypes extends ManageRecords
{
    protected static string $resource = ServiceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('إضافة نوع خدمة')];
    }
}
