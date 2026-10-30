<?php

namespace Modules\Syndicate\Filament\Resources\ProjectResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Syndicate\Filament\Resources\ProjectResource;
use Modules\Syndicate\Services\SupervisionService;
use Modules\Syndicate\Support\Actor;
use Modules\Syndicate\Support\SyndicateException;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected static ?string $title = 'تسجيل مشروع (موقع بناء)';

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(SupervisionService::class)->registerProject($data, Actor::fromUser(auth()->user()));
        } catch (SyndicateException $e) {
            Notification::make()->danger()->title('تعذر تسجيل المشروع')->body($e->getMessage())->send();
            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return ProjectResource::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم تسجيل المشروع بكود '.$this->getRecord()->code;
    }
}
