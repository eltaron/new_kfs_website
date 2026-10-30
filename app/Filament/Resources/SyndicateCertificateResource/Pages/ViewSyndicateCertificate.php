<?php

namespace App\Filament\Resources\SyndicateCertificateResource\Pages;

use App\Filament\Resources\SyndicateCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSyndicateCertificate extends ViewRecord
{
    protected static string $resource = SyndicateCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')->label('طباعة / PDF')->icon('heroicon-o-printer')
                ->visible(fn () => filled($this->getRecord()->number))
                ->url(fn () => $this->getRecord()->printUrl(), shouldOpenInNewTab: true),
        ];
    }
}
