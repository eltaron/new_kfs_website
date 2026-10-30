<?php

namespace Modules\SiteValidity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\SiteValidity\Support\SvAccess;

/** سجل التدقيق/الخط الزمني: من، متى، من حالة ← إلى حالة، ملاحظات. */
class RequestEvent extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = [
        'submitted' => 'تقديم الطلب',
        'set_appointment' => 'تحديد موعد المعاينة',
        'reschedule' => 'تعديل موعد المعاينة',
        'approve' => 'موافقة مهندس التنظيم',
        'engineer_reschedule' => 'مهندس التنظيم: تغيير موعد المعاينة',
        'confirm_appointment' => 'المواطن: قبول موعد المعاينة',
        'citizen_reschedule' => 'المواطن: رفض الموعد واختيار يوم آخر',
        'reject' => 'رفض الطلب',
        'geo_inspection' => 'المركز الجيومكاني: تمت المعاينة',
        'engineer_inspection' => 'مهندس التنظيم: تمت المعاينة (استمارة المعاينة)',
        'upload_attachments' => 'رفع المرفقات الفنية',
        'request_completion' => 'طلب استيفاء من المواطن',
        'submit_completion' => 'المواطن أرسل الاستيفاء',
        'edit_inspection' => 'مهندس التنظيم: تعديل استمارة المعاينة',
        'upload_final' => 'مهندس التنظيم: رفع الملف النهائي (بانتظار اعتماد مدير التنظيم)',
        'director_return' => 'رفض مدير التنظيم — أُعيد الطلب للمهندس',
        'complete' => 'اعتماد مدير التنظيم — تم الانتهاء',
        'override' => 'تعديل إداري للحالة',
        'assign' => 'إسناد الطلب',
        'admin_edit' => 'تعديل إداري لبيانات الطلب',
        'set_location' => 'تحديد موقع الأرض على الخريطة',
        'paid' => 'سداد الرسوم إلكترونياً',
        'payment_failed' => 'محاولة سداد غير ناجحة',
    ];

    protected $table = 'sv_request_events';

    protected $fillable = ['action', 'from_status', 'to_status', 'notes', 'meta', 'actor_name', 'actor_role'];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SiteValidityRequest::class, 'request_id');
    }

    public function title(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    public function actorLabel(): string
    {
        $role = SvAccess::roleLabel($this->actor_role);

        return $this->actor_name ? $this->actor_name . ' — ' . $role : $role;
    }

    /** هل يظهر هذا الحدث للمواطن؟ (الأحداث الداخلية مثل الإسناد لا تظهر). */
    public function visibleToCitizen(): bool
    {
        return ! in_array($this->action, ['assign', 'admin_edit', 'payment_failed', 'edit_inspection', 'director_return'], true);
    }
}
