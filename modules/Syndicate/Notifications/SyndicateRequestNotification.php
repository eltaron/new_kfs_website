<?php

namespace Modules\Syndicate\Notifications;

use Modules\Syndicate\Models\SyndicateRequest;

/**
 * تحديثات الطلبات: تسجيل طلب جديد (لموظفي النقابة + تأكيد لمقدم الطلب) أو رفض الطلب.
 * $kind: submitted | rejected | under_review
 */
class SyndicateRequestNotification extends SyndicateNotification
{
    public function __construct(SyndicateRequest $request, string $audience, public string $kind = 'submitted')
    {
        parent::__construct($request->project, null, $request, $audience);
    }

    public function level(): string
    {
        return match ($this->kind) {
            'rejected' => 'danger',
            'under_review' => 'warning',
            default => 'info',
        };
    }

    public function title(): string
    {
        $type = $this->request->type === SyndicateRequest::TYPE_CANCELLATION ? 'طلب إلغاء إشراف' : 'طلب إشراف';

        return match ($this->kind) {
            'rejected' => 'رفض ' . $type,
            'under_review' => $type . ' قيد المراجعة',
            default => $this->audience === 'syndicate' ? $type . ' جديد بانتظار النقابة' : 'تم تسجيل ' . $type,
        };
    }

    public function body(): string
    {
        $r = $this->request;

        return match ($this->kind) {
            'rejected' => 'تم رفض المعاملة رقم ' . $r->transaction_number . ($r->rejection_reason ? ' — السبب: ' . $r->rejection_reason : '') . '.',
            'under_review' => 'المعاملة رقم ' . $r->transaction_number . ' قيد المراجعة الآن بنقابة المهندسين.',
            default => $this->audience === 'syndicate'
                ? 'المعاملة رقم ' . $r->transaction_number . ' للمشروع ' . $this->project->code . ' (' . $this->project->center . ') — مقدم الطلب: ' . $r->applicant_name . '.'
                : 'تم تسجيل طلبكم برقم المعاملة ' . $r->transaction_number . '. يمكنكم متابعته من بوابة المحافظة أو لدى نقابة المهندسين.',
        };
    }
}
