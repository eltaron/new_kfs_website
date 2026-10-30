<?php

namespace Modules\Syndicate\Notifications;

use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;

/**
 * تعيين مهندس مشرف جديد لمشروع كان موقوفاً (أو استبدال المهندس):
 * يُرسل لمجلس المدينة، المالك، المهندس الجديد والمهندس السابق على نفس الخط الزمني للمشروع.
 */
class SupervisionRestoredNotification extends SyndicateNotification
{
    public function __construct(
        Project $project,
        Certificate $certificate,
        ?SyndicateRequest $request,
        string $audience,
        public ?Certificate $previousCertificate = null,
        public bool $wasSuspended = true,
    ) {
        parent::__construct($project, $certificate, $request, $audience);
    }

    public function level(): string
    {
        return 'success';
    }

    public function title(): string
    {
        return match ($this->audience) {
            'engineer' => 'تم إسناد الإشراف على موقع إليكم',
            'previous_engineer' => 'تعيين مهندس مشرف جديد على موقع كنتم تشرفون عليه',
            default => $this->wasSuspended ? 'عودة المشروع للعمل بمهندس مشرف جديد' : 'تغيير المهندس المشرف على المشروع',
        };
    }

    public function body(): string
    {
        $c = $this->certificate;
        $prev = $this->previousCertificate ? ' بدلاً من ' . $this->engineerLabel($this->previousCertificate) : '';

        return match ($this->audience) {
            'engineer' => 'صدرت شهادة الإشراف رقم ' . $c->number . ' باسمكم للمشروع ' . $this->project->code
                . ' بالعنوان: ' . $this->project->address . '.',
            'previous_engineer' => 'صدرت شهادة الإشراف رقم ' . $c->number . ' للمشروع ' . $this->project->code
                . ' باسم ' . $this->engineerLabel() . '، ولم يعد الإشراف على الموقع مسنداً إليكم.',
            default => 'صدرت شهادة الإشراف رقم ' . $c->number . ' للمشروع ' . $this->project->code . ' — المهندس المشرف الجديد: '
                . $this->engineerLabel() . $prev . '.' . ($this->wasSuspended ? ' حالة المشروع الآن: نشط ويمكن استئناف الأعمال.' : ''),
        };
    }
}
