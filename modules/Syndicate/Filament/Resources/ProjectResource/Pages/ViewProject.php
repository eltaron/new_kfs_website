<?php

namespace Modules\Syndicate\Filament\Resources\ProjectResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Syndicate\Filament\Concerns\SupervisionActions;
use Modules\Syndicate\Filament\Resources\ProjectResource;
use Modules\Syndicate\Models\Project;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    public function getTitle(): string
    {
        return 'المشروع '.$this->getRecord()->code;
    }

    public function getSubheading(): ?string
    {
        /** @var Project $p */
        $p = $this->getRecord();

        return $p->owner_name.' — '.$p->center.' — '.$p->work_status_label;
    }

    protected function getHeaderActions(): array
    {
        return [
            SupervisionActions::registerSupervisionRequest(),
            SupervisionActions::registerCancellationRequest(),
            SupervisionActions::printCertificate('printActive', fn ($record) => $record instanceof Project ? $record->activeCertificate : null)
                ->label('طباعة شهادة الإشراف السارية'),
            Actions\EditAction::make()->label('تعديل البيانات'),
        ];
    }
}
