<?php

namespace Modules\Marketplace\Filament\Admin\Resources\VendorResource\Pages;

use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Modules\Marketplace\Filament\Admin\Resources\VendorResource;
use Modules\Marketplace\Models\MarketVendor;

class ListVendors extends ListRecords
{
    protected static string $resource = VendorResource::class;

    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('الكل')];
        foreach (MarketVendor::STATUSES as $status => $label) {
            $tabs[$status] = Tab::make($label)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status))
                ->badge(fn () => MarketVendor::query()->where('status', $status)->count() ?: null);
        }

        return $tabs;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return MarketVendor::query()->where('status', MarketVendor::STATUS_PENDING)->exists() ? MarketVendor::STATUS_PENDING : 'all';
    }
}
