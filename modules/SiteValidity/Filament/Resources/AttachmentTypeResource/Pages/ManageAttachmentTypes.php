<?php

namespace Modules\SiteValidity\Filament\Resources\AttachmentTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Modules\SiteValidity\Filament\Resources\AttachmentTypeResource;

class ManageAttachmentTypes extends ManageRecords
{
    protected static string $resource = AttachmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('إضافة نوع مرفق')];
    }
}
