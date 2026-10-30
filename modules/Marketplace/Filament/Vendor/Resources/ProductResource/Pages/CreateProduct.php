<?php

namespace Modules\Marketplace\Filament\Vendor\Resources\ProductResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Marketplace\Filament\Vendor\Resources\ProductResource;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Services\OrderNotifier;
use Modules\Marketplace\Support\MarketplaceAccess;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $vendorId = MarketplaceAccess::currentVendorId();
        abort_if($vendorId === 0, 403);

        // The vendor never chooses the owner or publishes directly.
        $data['vendor_id'] = $vendorId;
        if (! array_key_exists($data['status'] ?? '', ProductResource::VENDOR_STATUSES)) {
            $data['status'] = MarketProduct::STATUS_PENDING;
        }
        unset($data['slug']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var MarketProduct $product */
        $product = $this->record;
        if ($product->status === MarketProduct::STATUS_PENDING) {
            app(OrderNotifier::class)->productSubmitted($product);
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return $this->record->status === MarketProduct::STATUS_PENDING
            ? 'تم حفظ المنتج وإرساله للمراجعة. سيظهر في المتجر بعد الموافقة.'
            : 'تم حفظ المنتج كمسودة.';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
