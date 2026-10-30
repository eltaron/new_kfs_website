<?php

namespace Modules\Marketplace\Filament\Admin\Resources\ProductResource\Pages;

use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Modules\Marketplace\Filament\Admin\Resources\ProductResource;
use Modules\Marketplace\Models\MarketProduct;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('بانتظار المراجعة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MarketProduct::STATUS_PENDING))
                ->badge(fn () => MarketProduct::query()->where('status', MarketProduct::STATUS_PENDING)->count() ?: null),
            'published' => Tab::make('منشور')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MarketProduct::STATUS_PUBLISHED)),
            'rejected' => Tab::make('مرفوض')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MarketProduct::STATUS_REJECTED)),
            'all' => Tab::make('الكل'),
        ];
    }
}
