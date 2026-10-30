<?php

namespace Modules\Syndicate\Http;

use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateEvent;
use Modules\Syndicate\Models\SyndicateRequest;

/** تحويل النماذج إلى موارد JSON:API (type / id / attributes / relationships / links). */
class Transformers
{
    public static function project(Project $p): array
    {
        return [
            'type' => 'projects',
            'id' => (string) $p->getKey(),
            'attributes' => [
                'code' => $p->code,
                'owner' => [
                    'name' => $p->owner_name,
                    'national_id' => $p->owner_national_id,
                    'phone' => $p->owner_phone,
                    'address' => $p->owner_address,
                ],
                'address' => $p->address,
                'center' => $p->center,
                'village' => $p->village,
                'plot_number' => $p->plot_number,
                'license' => [
                    'number' => $p->license_number,
                    'date' => $p->license_date?->toDateString(),
                    'authority' => $p->license_authority,
                ],
                'works_type' => $p->works_type,
                'execution_sub_type' => $p->execution_sub_type,
                'works_description' => $p->works_description,
                'works_value' => $p->works_value,
                'work_status' => $p->work_status,
                'work_status_label' => $p->work_status_label,
                'work_status_changed_at' => $p->work_status_changed_at?->toIso8601String(),
                'suspended_at' => $p->suspended_at?->toIso8601String(),
                'active_certificate_number' => $p->activeCertificate?->number,
            ],
            'links' => [
                'timeline' => route('syndicate.api.projects.timeline', $p->getKey()),
            ],
        ];
    }

    public static function request(SyndicateRequest $r, bool $withIncluded = true): array
    {
        $data = [
            'type' => 'requests',
            'id' => $r->transaction_number,
            'attributes' => [
                'transaction_number' => $r->transaction_number,
                'type' => $r->type,
                'type_label' => $r->type_label,
                'status' => $r->status,
                'status_label' => $r->status_label,
                'requested_by' => $r->requested_by,
                'channel' => $r->channel,
                'applicant' => [
                    'name' => $r->applicant_name,
                    'national_id' => $r->applicant_national_id,
                    'phone' => $r->applicant_phone,
                    'email' => $r->applicant_email,
                ],
                'reason' => $r->reason,
                'rejection_reason' => $r->rejection_reason,
                'submitted_at' => $r->created_at?->toIso8601String(),
                'reviewed_at' => $r->reviewed_at?->toIso8601String(),
                'completed_at' => $r->completed_at?->toIso8601String(),
            ],
            'relationships' => [
                'project' => ['data' => ['type' => 'projects', 'id' => (string) $r->project_id]],
                'target_certificate' => ['data' => $r->targetCertificate ? ['type' => 'certificates', 'id' => $r->targetCertificate->number] : null],
                'certificate' => ['data' => $r->certificate ? ['type' => 'certificates', 'id' => $r->certificate->number] : null],
            ],
            'links' => ['self' => route('syndicate.api.requests.show', $r->transaction_number)],
        ];

        if ($withIncluded) {
            $data['included'] = array_values(array_filter([
                $r->project ? self::project($r->project) : null,
                $r->targetCertificate ? self::certificate($r->targetCertificate) : null,
            ]));
        }

        return $data;
    }

    public static function certificate(Certificate $c): array
    {
        return [
            'type' => 'certificates',
            'id' => $c->number,
            'attributes' => [
                'number' => $c->number,
                'kind' => $c->kind,
                'kind_label' => $c->kind_label,
                'status' => $c->status,
                'status_label' => $c->status_label,
                'issued_at' => $c->issued_at?->toIso8601String(),
                'issued_by' => $c->issued_by_name,
                'cancelled_at' => $c->cancelled_at?->toIso8601String(),
                'superseded_at' => $c->superseded_at?->toIso8601String(),
                'engineer' => [
                    'name' => $c->engineer_name,
                    'syndicate_number' => $c->engineer_syndicate_number,
                    'national_id' => $c->engineer_national_id,
                    'specialization' => $c->engineer_department,
                    'phone' => $c->engineer_phone,
                    'email' => $c->engineer_email,
                ],
                'office' => [
                    'name' => $c->office_name,
                    'registration_number' => $c->office_registration_number,
                ],
                'owner' => ['name' => $c->owner_name, 'national_id' => $c->owner_national_id],
                'project_address' => $c->project_address,
                'notes' => $c->notes,
            ],
            'relationships' => [
                'project' => ['data' => $c->project_id ? ['type' => 'projects', 'id' => (string) $c->project_id] : null],
                'request' => ['data' => $c->request ? ['type' => 'requests', 'id' => $c->request->transaction_number] : null],
                'cancels' => ['data' => $c->cancels ? ['type' => 'certificates', 'id' => $c->cancels->number] : null],
            ],
            'links' => [
                'self' => route('syndicate.api.certificates.show', $c->number),
                'verify' => $c->verificationUrl(false),
            ],
        ];
    }

    public static function event(SyndicateEvent $e): array
    {
        return [
            'type' => 'events',
            'id' => (string) $e->getKey(),
            'attributes' => [
                'type' => $e->type,
                'title' => $e->title,
                'description' => $e->description,
                'actor' => ['type' => $e->actor_type, 'name' => $e->actor_name],
                'request' => $e->request?->transaction_number,
                'certificate' => $e->certificate?->number,
                'occurred_at' => $e->created_at?->toIso8601String(),
            ],
        ];
    }
}
