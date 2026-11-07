<?php

namespace Modules\RemovalGovernance\Models;

use App\Models\User;
use App\Concerns\HasPayments;
use App\Contracts\Payable;
use App\Services\Payments\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemovalOrder extends Model implements Payable
{
    use HasPayments;

    protected $fillable = [
        'violation_type',
        'license_number',
        'license_date',
        'licensed_works',
        'center',
        'local_unit',
        'street',
        'violation_area',
        'district',
        'owner_name',
        'owner_national_id',
        'owner_center',
        'owner_unit',
        'owner_street',
        'owner_district',
        'owner_governorate',
        'engineer_name',
        'engineer_national_id',
        'contractor_name',
        'contractor_national_id',
        'violation_plot',
        'violation_dimensions',
        'violation_cost',
        'violation_works',
        'stop_order_number',
        'stop_order_date',
        'stop_order_file',
        'violation_report_number',
        'report_date',
        'violation_report_file',
        'announcement_date',
        'status',
        'sketch_file',
        'photo_file',
        'created_by',
        'assigned_to',
        'assigned_by',
        'stage',
        'spatial_data',
        'pdf_file',
        'visa_file',
        'review_notes',
        'engineering_engineer_id',
        'spatial_manager_id',
        'spatial_member_id',
        'systems_specialist_id',
        'governor_office_id',
        'border_east',
        'border_west',
        'border_north',
        'border_south',
        'dimension_east',
        'dimension_north',
        'dimension_south',
        'dimension_west',
        'area',
        'engineer_description',
        'governor_decision_number',
        'governor_decision_date',
        'citizen_notification_time',
        'citizen_notification_file',
        'police_notification_time',
        'police_notification_file',
        'object_id',
        'failure_to_execute_the_demolition_order_file',
        'notification_to_the_revenue_department_file'
    ];

    protected $casts = [
        'license_date' => 'date',
        'stop_order_date' => 'date',
        'report_date' => 'date',
        'announcement_date' => 'date',
        'violation_cost' => 'decimal:2',
        'area' => 'decimal:2',
        'spatial_data' => 'array',
        'citizen_notification_time' => 'datetime',
        'police_notification_time' => 'datetime',
    ];

    // ── Workflow Stages ──
    const STAGE_CREATED              = 'created';
    const STAGE_ENGINEERING_REVIEW   = 'engineering_review';
    const STAGE_SPATIAL_PENDING      = 'spatial_pending';
    const STAGE_SPATIAL_DIMENSIONED  = 'spatial_dimensioned';
    const STAGE_PDF_READY            = 'pdf_ready';
    const STAGE_SYSTEMS_PROCESSING   = 'systems_processing';
    const STAGE_LOCAL_UNIT_APPROVAL  = 'local_unit_approval';
    const STAGE_GIS_PENDING          = 'gis_pending';   // بعد اعتماد الوحدة المحلية: رؤساء الأقسام يوجّهون للاعتماد
    const STAGE_VISA_PENDING         = 'visa_pending';
    const STAGE_COMPLETED            = 'completed';

    public static function stages(): array
    {
        return [
            self::STAGE_CREATED              => 'قرار جديد',
            self::STAGE_ENGINEERING_REVIEW   => 'مراجعة التنظيم',
            self::STAGE_SPATIAL_PENDING      => 'تحديد مسئول البعد المكاني',
            self::STAGE_SPATIAL_DIMENSIONED  => 'إضافة البعد المكاني',
            self::STAGE_PDF_READY            => 'مراجعة المركز الرئيسي',
            self::STAGE_SYSTEMS_PROCESSING   => 'التحليل المكاني',
            self::STAGE_LOCAL_UNIT_APPROVAL  => 'الاعتماد من الوحدة المحلية',
            self::STAGE_GIS_PENDING          => 'التوجيه للاعتماد',
            self::STAGE_VISA_PENDING         => 'الاعتماد من السلطة المختصة',
            self::STAGE_COMPLETED            => 'منتهية',
        ];
    }

    // ── Relations ──
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function engineeringEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineering_engineer_id');
    }

    public function spatialManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spatial_manager_id');
    }

    public function spatialMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spatial_member_id');
    }

    public function systemsSpecialist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'systems_specialist_id');
    }

    public function governorOffice(): BelongsTo
    {
        return $this->belongsTo(User::class, 'governor_office_id');
    }
    // ── Payable (violation fine) ──
    // Implemented for the unified payment layer, but NOT exposed to citizens:
    // no UI offers it and canBePaid() is off unless
    // config('services.efinance.removal_order_payments') is enabled.

    public function payableAmount(): string
    {
        return Money::format($this->violation_cost);
    }

    public function payableDescription(): string
    {
        return 'مقابل مخالفة — قرار إزالة #' . $this->id . ($this->stop_order_number ? ' (أمر إيقاف ' . $this->stop_order_number . ')' : '');
    }

    public function payableOwner(): ?User
    {
        return $this->owner_national_id ? User::where('national_id', $this->owner_national_id)->first() : null;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canBePaid(): bool
    {
        return (bool) config('services.efinance.removal_order_payments', false)
            && ! $this->isPaid()
            && Money::isPositive($this->violation_cost);
    }

    public function markPaid(\Modules\Payments\Models\Transaction $transaction): void
    {
        $this->forceFill(['payment_status' => 'paid', 'paid_at' => now()])->save();
    }

    public function markPaymentFailed(\Modules\Payments\Models\Transaction $transaction): void
    {
    }

    public function paymentReturnUrl(\Modules\Payments\Models\Transaction $transaction): string
    {
        return route('citizen.dashboard');
    }
}
