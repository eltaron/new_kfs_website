<?php

namespace Modules\Marketplace\Filament\Admin\Resources\VendorResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Marketplace\Filament\Admin\Resources\VendorResource;

class ViewVendor extends ViewRecord
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...array_map(
                fn (Actions\Action $a) => $a->after(fn () => $this->record->refresh()),
                VendorResource::reviewActions(Actions\Action::class),
            ),
            Actions\Action::make('storefront')
                ->label('صفحة المتجر')->icon('heroicon-o-eye')->color('gray')
                ->url(fn () => route('market.vendors.show', $this->record->slug))
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->isApproved()),
            Actions\EditAction::make(),
        ];
    }
}
