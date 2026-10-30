<?php

namespace Modules\Marketplace\Filament\Vendor\Resources\OrderItemResource\Pages;

use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Modules\Marketplace\Filament\Vendor\Resources\OrderItemResource;
use Modules\Marketplace\Models\MarketOrderItem;

class ListOrderItems extends ListRecords
{
    protected static string $resource = OrderItemResource::class;

    public function getTabs(): array
    {
        return [
            'open' => Tab::make('تحتاج إجراء')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('fulfillment_status', [MarketOrderItem::FULFILLMENT_PENDING, MarketOrderItem::FULFILLMENT_PROCESSING])),
            'shipped' => Tab::make('تم الشحن')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('fulfillment_status', MarketOrderItem::FULFILLMENT_SHIPPED)),
            'done' => Tab::make('تم التسليم')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('fulfillment_status', MarketOrderItem::FULFILLMENT_DELIVERED)),
            'all' => Tab::make('الكل'),
        ];
    }
}
