<?php

namespace Modules\Syndicate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** سجل الأحداث (الخط الزمني + التدقيق) للمشروع. */
class SyndicateEvent extends Model
{
    protected $table = 'syndicate_events';

    public const REQUEST_SUBMITTED = 'request_submitted';
    public const REQUEST_UNDER_REVIEW = 'request_under_review';
    public const REQUEST_REJECTED = 'request_rejected';
    public const SUPERVISION_ISSUED = 'supervision_issued';
    public const CERTIFICATE_SUPERSEDED = 'certificate_superseded';
    public const CANCELLATION_ISSUED = 'cancellation_issued';
    public const WORK_SUSPENDED = 'work_suspended';
    public const WORK_RESUMED = 'work_resumed';
    public const PROJECT_REGISTERED = 'project_registered';

    protected $fillable = [
        'project_id', 'request_id', 'certificate_id', 'type', 'title', 'description',
        'actor_type', 'actor_id', 'actor_name', 'payload',
    ];

    protected $casts = ['payload' => 'array'];

    public static function colors(): array
    {
        return [
            self::PROJECT_REGISTERED => 'gray',
            self::REQUEST_SUBMITTED => 'info',
            self::REQUEST_UNDER_REVIEW => 'warning',
            self::REQUEST_REJECTED => 'danger',
            self::SUPERVISION_ISSUED => 'success',
            self::CERTIFICATE_SUPERSEDED => 'gray',
            self::CANCELLATION_ISSUED => 'danger',
            self::WORK_SUSPENDED => 'danger',
            self::WORK_RESUMED => 'success',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(SyndicateRequest::class, 'request_id');
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class, 'certificate_id');
    }
}
