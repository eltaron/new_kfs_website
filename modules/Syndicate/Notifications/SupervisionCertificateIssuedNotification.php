<?php

namespace Modules\Syndicate\Notifications;

/** صدور شهادة إشراف على الموقع (للمالك، المهندس/المكتب، مجلس المدينة). */
class SupervisionCertificateIssuedNotification extends SyndicateNotification
{
    public function level(): string
    {
        return 'success';
    }

    public function title(): string
    {
        return $this->audience === 'engineer'
            ? 'تم إسناد الإشراف على موقع إليكم'
            : 'صدور شهادة إشراف على الموقع';
    }

    public function body(): string
    {
        $c = $this->certificate;

        return match ($this->audience) {
            'engineer' => 'صدرت شهادة الإشراف رقم ' . $c->number . ' باسمكم للإشراف على موقع المالك ' . $this->project->owner_name
                . ' بالعنوان: ' . $this->project->address . ' (' . $this->project->center . ').',
            'council' => 'صدرت شهادة الإشراف رقم ' . $c->number . ' للمشروع ' . $this->project->code . ' — المهندس المشرف: '
                . $this->engineerLabel() . ' (عضوية ' . $c->engineer_syndicate_number . ').',
            default => 'صدرت شهادة الإشراف رقم ' . $c->number . ' على موقعكم بالعنوان: ' . $this->project->address
                . ' — المهندس المشرف: ' . $this->engineerLabel() . '.',
        };
    }
}
