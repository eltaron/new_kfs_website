<?php

namespace Modules\Syndicate\Filament\Resources\ProjectResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Modules\Syndicate\Filament\Resources\ProjectResource;
use Modules\Syndicate\Models\SyndicateEvent;
use Modules\Syndicate\Services\SupervisionService;
use Modules\Syndicate\Support\Actor;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // حالة الأعمال والشهادة السارية تتغير فقط عبر آلة الحالات.
        $data = Arr::except($data, ['code', 'work_status', 'active_certificate_id', 'suspended_at', 'work_status_changed_at']);
        $record->fill($data);
        $dirty = array_keys($record->getDirty());
        $record->save();

        if ($dirty) {
            app(SupervisionService::class)->recordEvent($record, 'project_updated', 'تعديل بيانات المشروع',
                'الحقول المعدلة: '.implode('، ', $dirty), Actor::fromUser(auth()->user()));
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return ProjectResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
