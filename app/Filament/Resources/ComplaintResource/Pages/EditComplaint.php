<?php

namespace App\Filament\Resources\ComplaintResource\Pages;

use App\Filament\Resources\ComplaintResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditComplaint extends EditRecord
{
    protected static string $resource = ComplaintResource::class;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If the admin reply field was filled or changed, and was not null before, set the replied_at timestamp.
        if (filled($data['admin_reply']) && $this->record->admin_reply !== $data['admin_reply']) {
            $data['replied_at'] = now();
        }

        return $data;
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
