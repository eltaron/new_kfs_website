<?php

namespace Modules\Syndicate\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * طلب إشراف أو طلب إلغاء إشراف مسجل بمجلس المدينة برقم معاملة فريد.
 */
class SyndicateRequest extends Model
{
    protected $table = 'syndicate_requests';

    public const TYPE_SUPERVISION = 'supervision';
    public const TYPE_CANCELLATION = 'cancellation';

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    public const OPEN_STATUSES = [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW, self::STATUS_APPROVED];

    public const BY_CITIZEN = 'citizen';
    public const BY_ENGINEER = 'engineer';
    public const BY_COUNCIL = 'council';

    protected $fillable = [
        'transaction_number', 'type', 'status', 'requested_by', 'channel', 'project_id',
        'applicant_name', 'applicant_national_id', 'applicant_phone', 'applicant_email', 'applicant_user_id',
        'target_certificate_id', 'certificate_id', 'reason', 'notes', 'rejection_reason',
        'registered_by', 'reviewed_by', 'reviewed_at', 'completed_at', 'meta',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_SUPERVISION => 'طلب إشراف على موقع',
            self::TYPE_CANCELLATION => 'طلب إلغاء إشراف',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_SUBMITTED => 'مقدم',
            self::STATUS_UNDER_REVIEW => 'قيد المراجعة بالنقابة',
            self::STATUS_APPROVED => 'معتمد',
            self::STATUS_REJECTED => 'مرفوض',
            self::STATUS_COMPLETED => 'مكتمل - صدرت الشهادة',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_SUBMITTED => 'info',
            self::STATUS_UNDER_REVIEW => 'warning',
            self::STATUS_APPROVED => 'primary',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_COMPLETED => 'success',
        ];
    }

    public static function requesters(): array
    {
        return [
            self::BY_CITIZEN => 'المواطن (المالك)',
            self::BY_ENGINEER => 'المهندس / المكتب الهندسي',
            self::BY_COUNCIL => 'مجلس المدينة',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::types()[$this->type] ?? (string) $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? (string) $this->status;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class, 'certificate_id');
    }

    public function targetCertificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class, 'target_certificate_id');
    }
}
