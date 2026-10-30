<?php

namespace Modules\SiteValidity\Filament\Resources\RequestResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\SiteValidity\Filament\Resources\RequestResource;
use Modules\SiteValidity\Models\Geo\Centre;
use Modules\SiteValidity\Services\WorkflowService;
use Modules\SiteValidity\Support\SvAccess;

/** تعديل إداري لبيانات الطلب (مسؤول الخدمة فقط) — يُسجل في الخط الزمني. */
class EditRequest extends EditRecord
{
    protected static string $resource = RequestResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(SvAccess::isAdmin(auth()->user()), 403);
        parent::authorizeAccess();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $allowed = ['service_type_id', 'construction_type', 'applicant_capacity', 'fee', 'applicant_name', 'national_id', 'phone', 'center_id', 'address', 'notes'];
        $data = array_intersect_key($data, array_flip($allowed));
        $data = array_map(fn ($v) => is_string($v) ? trim(strip_tags($v)) : $v, $data);

        if (isset($data['center_id']) && (int) $data['center_id'] !== (int) $record->center_id) {
            $data['center_name'] = Centre::query()->whereKey($data['center_id'])->value('name');
            $data['village_id'] = null;
            $data['village_name'] = null;
        }

        $record->forceFill($data);
        $changed = array_keys($record->getDirty());
        $record->save();

        app(WorkflowService::class)->logAdminEdit($record, auth()->user(), $changed);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return RequestResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
