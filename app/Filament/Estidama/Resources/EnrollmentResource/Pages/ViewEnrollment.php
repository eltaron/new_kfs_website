<?php

namespace App\Filament\Estidama\Resources\EnrollmentResource\Pages;

use App\Filament\Estidama\Resources\EnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEnrollment extends ViewRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('تغيير الحالة'),
        ];
    }
}
