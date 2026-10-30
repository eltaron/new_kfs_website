<?php

namespace Modules\Syndicate\Filament\Resources\CertificateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Syndicate\Filament\Concerns\SupervisionActions;
use Modules\Syndicate\Filament\Resources\CertificateResource;
use Modules\Syndicate\Models\Certificate;

class ViewCertificate extends ViewRecord
{
    protected static string $resource = CertificateResource::class;

    public function getTitle(): string
    {
        /** @var Certificate $c */
        $c = $this->getRecord();

        return $c->kind_label.' '.$c->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            SupervisionActions::printCertificate(),
            Actions\Action::make('verify')->label('صفحة التحقق العامة')->icon('heroicon-o-qr-code')->color('gray')
                ->url(fn (Certificate $record) => $record->verificationUrl(false), shouldOpenInNewTab: true),
        ];
    }
}
