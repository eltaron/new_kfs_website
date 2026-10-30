<?php

namespace Modules\Syndicate\Notifications;

use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;

/** صدور شهادة إلغاء إشراف ⇒ المشروع بدون مهندس مشرف ويجب إيقاف الأعمال. */
class SupervisionCancelledNotification extends SyndicateNotification
{
    public function __construct(
        Project $project,
        Certificate $certificate,
        ?SyndicateRequest $request,
        string $audience,
        public ?Certificate $cancelledCertificate = null,
    ) {
        parent::__construct($project, $certificate, $request, $audience);
    }

    public function level(): string
    {
        return 'danger';
    }

    public function title(): string
    {
        return 'إلغاء الإشراف — يجب إيقاف الأعمال فوراً';
    }

    public function body(): string
    {
        $old = $this->cancelledCertificate;
        $base = 'صدرت شهادة إلغاء الإشراف رقم ' . $this->certificate->number
            . ($old ? ' بإلغاء شهادة الإشراف ' . $old->number . ' (' . $this->engineerLabel($old) . ')' : '')
            . ' للمشروع ' . $this->project->code . ' بالعنوان: ' . $this->project->address . '. ';

        return $base . match ($this->audience) {
            'engineer' => 'انتهت مسؤوليتكم عن الإشراف على هذا الموقع اعتباراً من تاريخه.',
            'council' => 'المشروع الآن "موقوف - بدون إشراف"؛ يجب متابعة إيقاف الأعمال بالموقع لحين صدور شهادة إشراف جديدة.',
            default => 'لم يعد للمشروع مهندس مشرف، ويجب إيقاف جميع الأعمال لحين صدور شهادة إشراف جديدة من النقابة.',
        };
    }
}
