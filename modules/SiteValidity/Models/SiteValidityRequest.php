<?php

namespace Modules\SiteValidity\Models;

use App\Concerns\HasPayments;
use App\Contracts\Payable;
use App\Models\User;
use App\Services\Payments\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvStatus;

/**
 * طلب صلاحية الموقع.
 *
 * التحولات بين الحالات تتم فقط عبر Modules\SiteValidity\Services\WorkflowService
 * (حقول الحالة والتواريخ ليست mass-assignable).
 *
 * Payable: route('payments.pay', ['type' => 'site_validity_request', 'id' => $request->id])
 */
class SiteValidityRequest extends Model implements Payable
{
    use HasPayments {
        isPaid as protected hasCompletedTransaction;
    }

    protected $table = 'sv_requests';

    /** بيانات يدخلها المواطن فقط — الحالة والرسوم والتواريخ تُضبط عبر forceFill داخل الخدمة. */
    public const CAPACITY_OWNER = 'owner';
    public const CAPACITY_AGENT = 'agent';
    public const CAPACITIES = [self::CAPACITY_OWNER => 'المالك بنفسه', self::CAPACITY_AGENT => 'وكيل عن المالك'];

    public const CONSTRUCTION_NEW = 'new';
    public const CONSTRUCTION_EXTENSION = 'extension';
    public const CONSTRUCTION_TYPES = [self::CONSTRUCTION_NEW => 'إنشاء جديد', self::CONSTRUCTION_EXTENSION => 'تعلية'];

    public const CONFIRMATION_PENDING = 'pending';
    public const CONFIRMATION_ACCEPTED = 'accepted';

    protected $fillable = [
        'service_type_id', 'construction_type', 'applicant_name', 'national_id', 'phone', 'applicant_capacity',
        'center_id', 'village_id', 'center_name', 'village_name', 'address', 'notes', 'latitude', 'longitude',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'appointment_at' => 'datetime',
        'approved_at' => 'datetime',
        'geo_inspected_at' => 'datetime',
        'engineer_inspected_at' => 'datetime',
        'attachments_uploaded_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'submitted_at' => 'datetime',
        'inspection_data' => 'array',
        'existing_building_height' => 'decimal:2',
        'inspection_updated_at' => 'datetime',
        'appointment_confirmed_at' => 'datetime',
        'completion_rounds' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    // ───────────────────────────── relations

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function geoUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'geo_user_id');
    }

    public function engineerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_user_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(RequestFile::class, 'request_id')->orderBy('id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(Completion::class, 'request_id')->orderBy('round');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RequestEvent::class, 'request_id')->orderBy('created_at')->orderBy('id');
    }

    // ───────────────────────────── scopes

    public function scopeStatus(Builder $query, string|array $status): Builder
    {
        return $query->whereIn('status', (array) $status);
    }

    /** ما يراه كل دور داخل اللوحة (المهندس لا يرى الطلبات الجديدة قبل تحديد الموعد). */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (SvAccess::isAdmin($user) || SvAccess::isGeo($user) || SvAccess::isDirector($user)) {
            return $query;
        }
        if (SvAccess::isEngineer($user)) {
            return $query->where('status', '!=', SvStatus::SUBMITTED);
        }
        if (SvAccess::isTech($user)) {
            return $query->where('created_by', $user->getKey()); // المركز التكنولوجي: ما أدخله فقط
        }

        return $query->where('user_id', $user?->getKey() ?? '__none__');
    }

    /** طلبات تجاوز موعد معاينتها ولم تُستكمل علامات المعاينة. */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereIn('status', [SvStatus::APPOINTMENT_SET, SvStatus::APPROVED])
            ->whereNotNull('appointment_at')
            ->where('appointment_at', '<', now()->startOfDay());
    }

    // ───────────────────────────── helpers

    public function statusLabel(): string
    {
        return SvStatus::label($this->status);
    }

    public function statusColor(): string
    {
        return SvStatus::color($this->status);
    }

    public function capacityLabel(): string
    {
        return self::CAPACITIES[$this->applicant_capacity ?? self::CAPACITY_OWNER] ?? (string) $this->applicant_capacity;
    }

    public function constructionTypeLabel(): string
    {
        return self::CONSTRUCTION_TYPES[$this->construction_type ?? self::CONSTRUCTION_NEW] ?? (string) $this->construction_type;
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (string) $this->user_id === (string) $user->getKey();
    }

    public function openCompletion(): ?Completion
    {
        return $this->completions()->whereNull('replied_at')->latest('id')->first();
    }

    public function finalFile(): ?RequestFile
    {
        return $this->files()->where('kind', RequestFile::KIND_FINAL)->latest('id')->first();
    }

    /** الملف النهائي لا يظهر للمواطن إلا بعد اعتماد مدير التنظيم. */
    public function finalReleased(): bool
    {
        return $this->status === SvStatus::COMPLETED;
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [SvStatus::COMPLETED, SvStatus::REJECTED], true);
    }

    /** حدود تقريبية لمحافظة كفر الشيخ للتحقق من إحداثيات موقع الأرض: [minLat, maxLat, minLng, maxLng]. */
    public const KFS_BOUNDS = [30.85, 31.70, 30.30, 31.50];

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function locationLabel(): string
    {
        return trim(implode(' — ', array_filter([$this->center_name, $this->village_name])));
    }

    // ───────────────────────────── Payable

    public function payableAmount(): string
    {
        return Money::format($this->fee);
    }

    public function payableDescription(): string
    {
        return 'رسوم طلب صلاحية الموقع رقم ' . ($this->number ?: $this->getKey());
    }

    public function payableOwner(): ?User
    {
        return $this->user;
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null || ($this->exists && $this->hasCompletedTransaction());
    }

    public function canBePaid(): bool
    {
        return ! $this->isPaid()
            && $this->status !== SvStatus::REJECTED
            && Money::isPositive($this->payableAmount());
    }

    /** المعاملة بدون نوع صريح (توسيع مسموح للمعامل) حتى لا تعتمد الوحدة على نماذج وحدة الدفع. */
    public function markPaid($transaction): void
    {
        if ($this->paid_at !== null) {
            return;
        }

        $this->forceFill([
            'paid_at' => now(),
            'paid_amount' => Money::format($transaction->amount ?? $this->fee),
            'payment_reference' => (string) ($transaction->sender_request_number ?? $transaction->getKey()),
        ])->save();

        $event = new RequestEvent([
            'action' => 'paid',
            'from_status' => $this->status,
            'to_status' => $this->status,
            'notes' => 'تم سداد ' . Money::format($transaction->amount ?? $this->fee) . ' ج.م عبر e-Finance.',
            'actor_name' => 'منظومة الدفع الإلكتروني',
            'actor_role' => 'system',
        ]);
        $event->request_id = $this->getKey();
        $event->save();
    }

    public function markPaymentFailed($transaction): void
    {
        if ($this->paid_at !== null) {
            return;
        }

        $event = new RequestEvent([
            'action' => 'payment_failed',
            'from_status' => $this->status,
            'to_status' => $this->status,
            'notes' => (string) ($transaction->failure_reason ?? 'لم تكتمل عملية الدفع.'),
            'actor_name' => 'منظومة الدفع الإلكتروني',
            'actor_role' => 'system',
        ]);
        $event->request_id = $this->getKey();
        $event->save();
    }

    public function paymentReturnUrl($transaction): string
    {
        return route('site-validity.requests.show', $this);
    }
}
