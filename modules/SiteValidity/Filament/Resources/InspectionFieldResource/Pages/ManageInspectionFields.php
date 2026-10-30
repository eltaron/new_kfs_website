<?php

namespace Modules\SiteValidity\Filament\Resources\InspectionFieldResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Modules\SiteValidity\Filament\Resources\InspectionFieldResource;

class ManageInspectionFields extends ManageRecords
{
    protected static string $resource = InspectionFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('إضافة بند معاينة')];
    }
}
