<?php

namespace Modules\Syndicate\Filament\Resources\CertificateResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Modules\Syndicate\Filament\Resources\CertificateResource;
use Modules\Syndicate\Filament\Resources\RequestResource;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\SyndicateRequest;

class ListCertificates extends ListRecords
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // مجلس المدينة يبحث برقم الشهادة أو برقم المعاملة.
            Actions\Action::make('lookup')
                ->label('بحث برقم الشهادة / المعاملة')
                ->icon('heroicon-o-magnifying-glass')
                ->form([Forms\Components\TextInput::make('number')->label('رقم الشهادة أو رقم المعاملة')
                    ->placeholder('SYN-SUP-2026-000045 / SYN-REQ-2026-000123')->required()->maxLength(40)])
                ->action(function (array $data, Actions\Action $action) {
                    $n = strtoupper(trim($data['number']));
                    if ($certificate = Certificate::where('number', $n)->first()) {
                        $action->redirect(CertificateResource::getUrl('view', ['record' => $certificate]));

                        return;
                    }
                    if ($request = SyndicateRequest::where('transaction_number', $n)->first()) {
                        $action->redirect($request->certificate_id
                            ? CertificateResource::getUrl('view', ['record' => $request->certificate_id])
                            : RequestResource::getUrl('view', ['record' => $request]));

                        return;
                    }
                    Notification::make()->danger()->title('لا توجد شهادة أو معاملة بهذا الرقم')->send();
                    $action->halt();
                }),
        ];
    }
}
