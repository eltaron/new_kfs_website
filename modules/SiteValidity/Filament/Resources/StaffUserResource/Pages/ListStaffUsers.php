<?php

namespace Modules\SiteValidity\Filament\Resources\StaffUserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\SiteValidity\Filament\Resources\StaffUserResource;

class ListStaffUsers extends ListRecords
{
    protected static string $resource = StaffUserResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('إضافة حساب')];
    }
}
