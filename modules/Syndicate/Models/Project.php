<?php

namespace Modules\Syndicate\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * موقع البناء (المشروع) المسجل لدى مجلس المدينة.
 *
 * @property string $work_status pending | active | suspended
 */
class Project extends Model
{
    protected $table = 'syndicate_projects';

    public const WORK_PENDING = 'pending';
    public const WORK_ACTIVE = 'active';
    public const WORK_SUSPENDED = 'suspended';

    protected $fillable = [
        'code', 'owner_name', 'owner_national_id', 'owner_phone', 'owner_email', 'owner_address', 'owner_user_id',
        'address', 'center', 'village', 'plot_number', 'latitude', 'longitude',
        'license_number', 'license_date', 'license_authority', 'works_type', 'execution_sub_type',
        'works_description', 'works_value', 'work_status', 'work_status_changed_at', 'suspended_at',
        'active_certificate_id', 'created_by',
    ];

    protected $casts = [
        'license_date' => 'date',
        'works_value' => 'decimal:2',
        'work_status_changed_at' => 'datetime',
        'suspended_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public static function workStatuses(): array
    {
        return [
            self::WORK_PENDING => 'بانتظار تعيين مهندس مشرف',
            self::WORK_ACTIVE => 'نشط - تحت الإشراف',
            self::WORK_SUSPENDED => 'موقوف - بدون إشراف',
        ];
    }

    public static function workStatusColors(): array
    {
        return [
            self::WORK_PENDING => 'gray',
            self::WORK_ACTIVE => 'success',
            self::WORK_SUSPENDED => 'danger',
        ];
    }

    public function getWorkStatusLabelAttribute(): string
    {
        return self::workStatuses()[$this->work_status] ?? (string) $this->work_status;
    }

    public function isSuspended(): bool
    {
        return $this->work_status === self::WORK_SUSPENDED;
    }

    public function isActive(): bool
    {
        return $this->work_status === self::WORK_ACTIVE;
    }

    public function requests(): HasMany
    {
        return $this->hasMany(SyndicateRequest::class, 'project_id')->latest('id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'project_id')->latest('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SyndicateEvent::class, 'project_id')->latest('id');
    }

    public function activeCertificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class, 'active_certificate_id');
    }

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function openRequest(string $type): ?SyndicateRequest
    {
        return $this->requests()->where('type', $type)->open()->first();
    }

    /** المشروعات التي يملكها المستخدم أو يشرف عليها (بالرقم القومي). */
    public function scopeVisibleToCitizen(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('owner_user_id', $user->getKey());
            if (filled($user->national_id)) {
                $q->orWhere('owner_national_id', $user->national_id)
                    ->orWhereHas('certificates', fn (Builder $c) => $c->where('engineer_national_id', $user->national_id));
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
