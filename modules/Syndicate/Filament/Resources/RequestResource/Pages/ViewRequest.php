<?php

namespace Modules\Syndicate\Filament\Resources\RequestResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Syndicate\Filament\Concerns\SupervisionActions;
use Modules\Syndicate\Filament\Resources\RequestResource;
use Modules\Syndicate\Models\SyndicateRequest;

class ViewRequest extends ViewRecord
{
    protected static string $resource = RequestResource::class;

    public function getTitle(): string
    {
        return 'المعاملة '.$this->getRecord()->transaction_number;
    }

    public function getSubheading(): ?string
    {
        /** @var SyndicateRequest $r */
        $r = $this->getRecord();

        return $r->type_label.' — '.$r->status_label;
    }

    protected function getHeaderActions(): array
    {
        return [
            SupervisionActions::markUnderReview(),
            SupervisionActions::issueSupervision(),
            SupervisionActions::issueCancellation(),
            SupervisionActions::reject(),
            SupervisionActions::printCertificate('printIssued', fn ($record) => $record instanceof SyndicateRequest ? $record->certificate : null)
                ->label('طباعة الشهادة الصادرة'),
        ];
    }
}
