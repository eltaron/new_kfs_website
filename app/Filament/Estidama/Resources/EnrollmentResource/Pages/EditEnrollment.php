<?php

namespace App\Filament\Estidama\Resources\EnrollmentResource\Pages;

use App\Filament\Estidama\Resources\EnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEnrollment extends EditRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Fee waiver: a "free" enrollment owes nothing.
        if (($data['payment_status'] ?? null) === 'free') {
            $data['amount'] = 0;
        }
        if (($data['payment_status'] ?? null) === 'unpaid' && (float) $this->getRecord()->amount <= 0) {
            $data['amount'] = $this->getRecord()->trainingProgram?->price ?? 0;
        }
        if (($data['status'] ?? null) === 'completed' && ! $this->getRecord()->completed_at) {
            $data['completed_at'] = now();
        }

        return $data;
    }
}
