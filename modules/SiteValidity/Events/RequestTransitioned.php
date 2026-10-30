<?php

namespace Modules\SiteValidity\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\SiteValidity\Models\SiteValidityRequest;

/**
 * يُطلق بعد كل إجراء ناجح على الطلب (بعد اعتماد المعاملة في قاعدة البيانات).
 * $action: submitted | set_appointment | reschedule | approve | reject | geo_inspection | engineer_inspection
 *          | upload_attachments | request_completion | submit_completion | complete | override | assign
 */
class RequestTransitioned
{
    use Dispatchable;

    public function __construct(
        public SiteValidityRequest $request,
        public string $action,
        public ?string $from,
        public ?string $to,
        public ?User $actor = null,
        public ?string $notes = null,
    ) {
    }
}
