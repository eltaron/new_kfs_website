<?php

namespace App\Filament\Resources\SyndicateCertificateResource\Pages;

use App\Filament\Resources\SyndicateCertificateResource;
use Filament\Resources\Pages\ListRecords;

class ListSyndicateCertificates extends ListRecords
{
    protected static string $resource = SyndicateCertificateResource::class;

    public function getSubheading(): ?string
    {
        return 'عرض فقط — الإصدار من لوحة نقابة المهندسين (/syndicate) والتسجيل من لوحة مجلس المدينة (/council).';
    }
}
