<?php

namespace Modules\Marketplace\Filament\Vendor\Resources\ProductResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Marketplace\Filament\Vendor\Resources\ProductResource;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Services\OrderNotifier;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    private bool $submittedForReview = false;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var MarketProduct $record */
        $record = $this->record;
        $wasPublished = $record->status === MarketProduct::STATUS_PUBLISHED;
        $requested = (string) ($data['status'] ?? '');

        $allowed = array_keys(ProductResource::VENDOR_STATUSES);
        if ($wasPublished) {
            $allowed[] = MarketProduct::STATUS_PUBLISHED;
        }
        if (! in_array($requested, $allowed, true)) {
            $requested = MarketProduct::STATUS_PENDING;
        }

        // Changing the wording of a published product sends it back to review.
        if ($requested === MarketProduct::STATUS_PUBLISHED
            && (trim((string) ($data['name'] ?? '')) !== $record->name
                || trim((string) ($data['description'] ?? '')) !== trim((string) $record->description))) {
            $requested = MarketProduct::STATUS_PENDING;
        }

        $this->submittedForReview = $requested === MarketProduct::STATUS_PENDING && $record->status !== MarketProduct::STATUS_PENDING;
        $data['status'] = $requested;
        unset($data['vendor_id'], $data['slug']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->submittedForReview) {
            app(OrderNotifier::class)->productSubmitted($this->record);
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return $this->submittedForReview
            ? 'تم حفظ التعديلات وإرسال المنتج للمراجعة.'
            : 'تم حفظ التعديلات.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_public')
                ->label('عرض في المتجر')->icon('heroicon-o-eye')->color('gray')
                ->url(fn () => route('market.products.show', $this->record->slug))
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->status === MarketProduct::STATUS_PUBLISHED),
            Actions\DeleteAction::make(),
        ];
    }
}
