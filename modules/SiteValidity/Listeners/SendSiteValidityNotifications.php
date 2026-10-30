<?php

namespace Modules\SiteValidity\Listeners;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\SiteValidity\Events\RequestTransitioned;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Notifications\SiteValidityNotification;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvStatus;

/**
 * يرسل الإشعار للفاعل التالي في المسار وللمواطن عند كل انتقال.
 * فشل قناة (بريد مثلاً) لا يُفشل العملية.
 */
class SendSiteValidityNotifications
{
    public function handle(RequestTransitioned $event): void
    {
        $r = $event->request;
        $n = $r->number;

        switch ($event->action) {
            case 'submitted':
                $this->staff($r, SvAccess::GEO, 'طلب صلاحية موقع جديد', "ورد الطلب {$n} ويحتاج تحديد موعد المعاينة.", 'warning', $event->actor);
                $this->citizen($r, 'تم استلام طلبك', "تم استلام طلب صلاحية الموقع رقم {$n}. سيتم تحديد موعد المعاينة وإخطارك.", 'success');
                break;

            case 'set_appointment':
            case 'reschedule':
                $when = $r->appointment_at?->format('Y-m-d H:i');
                $this->staff($r, SvAccess::ENGINEER, $event->action === 'reschedule' ? 'تعديل موعد معاينة' : 'طلب بانتظار الموافقة',
                    "الطلب {$n}: موعد المعاينة {$when}.", 'info', $event->actor);
                $this->citizen($r, 'موعد المعاينة', "تم تحديد موعد معاينة الموقع للطلب {$n}: {$when}.", 'info');
                break;

            case 'engineer_reschedule':
                $when = $r->appointment_at?->format('Y-m-d H:i');
                $this->staff($r, SvAccess::GEO, 'غيّر المهندس موعد المعاينة', "الطلب {$n}: الموعد الجديد {$when}.", 'info', $event->actor);
                $this->citizen($r, 'موعد معاينة جديد — يرجى التأكيد', "تم تحديد موعد معاينة الطلب {$n}: {$when}. يرجى قبوله أو اختيار يوم آخر من صفحة الطلب.", 'warning');
                break;

            case 'confirm_appointment':
                $this->staff($r, SvAccess::GEO, 'المواطن أكد الموعد', "أكد المواطن موعد معاينة الطلب {$n}: " . $r->appointment_at?->format('Y-m-d H:i'), 'success', null);
                $this->staff($r, SvAccess::ENGINEER, 'المواطن أكد الموعد', "أكد المواطن موعد معاينة الطلب {$n}: " . $r->appointment_at?->format('Y-m-d H:i'), 'success', null);
                break;

            case 'citizen_reschedule':
                $when = $r->appointment_at?->format('Y-m-d H:i');
                $this->staff($r, SvAccess::GEO, 'المواطن اختار موعداً آخر', "رفض المواطن موعد الطلب {$n} واختار: {$when}.", 'warning', null);
                $this->staff($r, SvAccess::ENGINEER, 'المواطن اختار موعداً آخر', "رفض المواطن موعد الطلب {$n} واختار: {$when}.", 'warning', null);
                break;

            case 'approve':
                $this->staff($r, SvAccess::GEO, 'تمت موافقة مهندس التنظيم', "الطلب {$n} معتمد — المعاينة في موعدها.", 'success', $event->actor);
                $this->citizen($r, 'تم اعتماد طلبك', "تم اعتماد الطلب {$n} وموعد المعاينة " . $r->appointment_at?->format('Y-m-d H:i') . '. يرجى قبول الموعد أو اختيار يوم آخر من صفحة الطلب.', 'success');
                break;

            case 'geo_inspection':
            case 'engineer_inspection':
                if ($event->to === SvStatus::INSPECTED) {
                    $this->staff($r, SvAccess::GEO, 'رفع المرفقات الفنية', "اكتملت معاينة الطلب {$n}. يرجى رفع الرفع المساحي وملف الكاد.", 'warning', $event->actor);
                    $this->citizen($r, 'تمت المعاينة', "تمت المعاينة الميدانية للطلب {$n}.", 'info');
                }
                break;

            case 'upload_attachments':
                $this->staff($r, SvAccess::ENGINEER, 'طلب بانتظار قرارك', "تم رفع المرفقات الفنية للطلب {$n}.", 'warning', $event->actor);
                break;

            case 'request_completion':
                $by = $event->from === SvStatus::INSPECTED ? 'المركز الجيومكاني' : 'مهندس التنظيم';
                $c = $r->openCompletion();
                $c?->isPayment()
                    ? $this->citizen($r, 'مطلوب سداد رسوم استيفاء', "{$by} يطلب سداد " . number_format((float) $c->amount, 2) . " ج.م للطلب {$n}: " . ($event->notes ?? '') . ' — السداد من صفحة الطلب.', 'danger')
                    : $this->citizen($r, 'مطلوب استيفاء', "{$by} يطلب استيفاء بعض البيانات للطلب {$n}: " . ($event->notes ?? ''), 'danger');
                break;

            case 'submit_completion':
                $event->to === SvStatus::INSPECTED
                    ? $this->staff($r, SvAccess::GEO, 'تم الاستيفاء من المواطن', "أرسل المواطن الاستيفاء المطلوب للطلب {$n}. يمكنك استكمال رفع المرفقات.", 'warning', $event->actor)
                    : $this->staff($r, SvAccess::ENGINEER, 'تم الاستيفاء من المواطن', "أرسل المواطن الاستيفاء المطلوب للطلب {$n}.", 'warning', $event->actor);
                break;

            case 'upload_final':
                $this->staff($r, SvAccess::DIRECTOR, 'طلب بانتظار اعتمادك', "رفع مهندس التنظيم الملف النهائي للطلب {$n} — بانتظار اعتماد الصلاحية.", 'warning', $event->actor);
                break;

            case 'director_return':
                $this->staff($r, SvAccess::ENGINEER, 'رفض مدير التنظيم الطلب', "رفض مدير التنظيم الطلب {$n} وأعاده إليك. السبب: " . ($event->notes ?? ''), 'danger', $event->actor);
                break;

            case 'complete':
                $this->citizen($r, 'تم الانتهاء من طلبك', "تم الانتهاء من الطلب {$n} ويمكنك تنزيل الملف النهائي من صفحة الطلب.", 'success');
                $this->staff($r, SvAccess::GEO, 'تم الانتهاء من طلب', "اعتمد مدير التنظيم الطلب {$n} وأصبح الملف النهائي متاحاً.", 'success', $event->actor);
                $this->staff($r, SvAccess::ENGINEER, 'تم اعتماد الطلب', "اعتمد مدير التنظيم الطلب {$n}.", 'success', $event->actor);
                break;

            case 'reject':
                $this->citizen($r, 'تم رفض الطلب', "تم رفض الطلب {$n}. السبب: " . ($event->notes ?? ''), 'danger');
                $this->staff($r, SvAccess::GEO, 'تم رفض طلب', "رفض مهندس التنظيم الطلب {$n}.", 'danger', $event->actor);
                break;

            case 'override':
                $this->citizen($r, 'تحديث حالة طلبك', "تم تحديث حالة الطلب {$n} إلى: " . SvStatus::label($event->to), 'info');
                break;
        }
    }

    protected function staff(SiteValidityRequest $r, string $role, string $title, string $body, string $level, ?User $actor): void
    {
        $assigned = match ($role) {
            SvAccess::GEO => $r->geoUser,
            SvAccess::ENGINEER => $r->engineerUser,
            default => null,
        };
        $to = $assigned ? collect([$assigned]) : $this->usersWithRole($role);
        $to = $to->reject(fn (User $u) => $actor !== null && $u->is($actor));

        $this->send($to, new SiteValidityNotification($r, $title, $body, 'staff', $level));
    }

    protected function citizen(SiteValidityRequest $r, string $title, string $body, string $level): void
    {
        if ($r->user) {
            $this->send(collect([$r->user]), new SiteValidityNotification($r, $title, $body, 'citizen', $level));
        }
    }

    protected function usersWithRole(string $role): Collection
    {
        try {
            return User::role($role)->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', ['rejected', 'inactive', 'suspended', 'blocked', 'disabled']))->get();
        } catch (\Throwable) {
            return collect(); // الدور غير موجود بعد
        }
    }

    protected function send(Collection $users, SiteValidityNotification $notification): void
    {
        if ($users->isEmpty()) {
            return;
        }

        try {
            Notification::send($users, $notification);
        } catch (\Throwable $e) {
            Log::warning('site-validity notification failed: ' . $e->getMessage(), ['request' => $notification->request->getKey()]);
        }
    }
}
