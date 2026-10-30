<?php

namespace Modules\Marketplace\Filament\Vendor\Resources\ProductResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Marketplace\Filament\Vendor\Resources\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('إضافة منتج جديد')->icon('heroicon-o-plus'),
        ];
    }
}
