<?php

namespace Modules\Syndicate\Filament\Resources\RequestResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Syndicate\Filament\Resources\RequestResource;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Services\SupervisionService;
use Modules\Syndicate\Support\Actor;
use Modules\Syndicate\Support\SyndicateException;

class CreateRequest extends CreateRecord
{
    protected static string $resource = RequestResource::class;

    protected static ?string $title = 'تسجيل طلب بمجلس المدينة';

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(SupervisionService::class)->submitRequest(
                Project::findOrFail($data['project_id']),
                $data['type'],
                [
                    'name' => $data['applicant_name'],
                    'national_id' => $data['applicant_national_id'] ?? null,
                    'phone' => $data['applicant_phone'] ?? null,
                    'email' => $data['applicant_email'] ?? null,
                ],
                $data['requested_by'],
                Actor::fromUser(auth()->user()),
                'counter',
                $data['reason'] ?? null,
            );
        } catch (SyndicateException $e) {
            Notification::make()->danger()->title('تعذر تسجيل الطلب')->body($e->getMessage())->persistent()->send();
            $this->halt();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم تسجيل الطلب برقم المعاملة '.$this->getRecord()->transaction_number;
    }

    protected function getRedirectUrl(): string
    {
        return RequestResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
