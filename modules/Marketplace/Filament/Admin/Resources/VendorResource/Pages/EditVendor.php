<?php

namespace Modules\Marketplace\Filament\Admin\Resources\VendorResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Marketplace\Filament\Admin\Resources\VendorResource;

class EditVendor extends EditRecord
{
    protected static string $resource = VendorResource::class;

    /** commission_rate is admin-only and deliberately not mass-assignable. */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $commission = $data['commission_rate'] ?? null;
        unset($data['commission_rate']);

        $record->fill($data);
        $record->forceFill(['commission_rate' => ($commission === null || $commission === '') ? null : $commission]);
        $record->save();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
