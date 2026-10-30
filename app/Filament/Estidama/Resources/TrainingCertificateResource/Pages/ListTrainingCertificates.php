<?php

namespace App\Filament\Estidama\Resources\TrainingCertificateResource\Pages;

use App\Filament\Estidama\Resources\TrainingCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrainingCertificates extends ListRecords
{
    protected static string $resource = TrainingCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('verifyPage')
                ->label('صفحة التحقق العامة')
                ->icon('heroicon-o-magnifying-glass')
                ->color('gray')
                ->url(route('lms.certificates.verify'), true),
        ];
    }
}
