<?php

namespace Modules\Marketplace\Filament\Admin\Resources\ProductResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Marketplace\Filament\Admin\Resources\ProductResource;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Services\OrderNotifier;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    /** Moderation fields (is_featured, moderation_note) are not mass-assignable. */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var MarketProduct $record */
        $statusChanged = ($data['status'] ?? $record->status) !== $record->status;

        $record->fill(collect($data)->except(['is_featured', 'moderation_note', 'vendor_id'])->all());
        $record->forceFill([
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'moderation_note' => $data['moderation_note'] ?? null,
        ]);
        $record->save();

        if ($statusChanged && in_array($record->status, [MarketProduct::STATUS_PUBLISHED, MarketProduct::STATUS_REJECTED], true)) {
            app(OrderNotifier::class)->productModerated($record);
        }

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
