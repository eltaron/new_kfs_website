<?php

namespace Modules\SiteValidity\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Modules\SiteValidity\Filament\Resources\RequestResource;
use Modules\SiteValidity\Models\SiteValidityRequest;
use Modules\SiteValidity\Services\WorkflowException;
use Modules\SiteValidity\Services\WorkflowService;
use Modules\SiteValidity\Support\SvAccess;
use Modules\SiteValidity\Support\SvRequestFilters;
use Modules\SiteValidity\Support\SvStatus;

/**
 * تقويم مواعيد المعاينة — للمركز الجيومكاني ومهندس التنظيم (ومسؤول الخدمة).
 * «مواعيدي»: الطلبات المسندة لي أو غير المسندة لأحد من دوري. السحب والإفلات لإعادة الجدولة
 * يمر عبر WorkflowService::setAppointment (نفس قواعد زر «تعديل الموعد» + الخط الزمني + الإشعار).
 */
class AppointmentsCalendar extends Page
{
    protected static string $view = 'sitevalidity::filament.appointments-calendar';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'الطلبات';

    protected static ?string $navigationLabel = 'تقويم المواعيد';

    protected static ?string $title = 'تقويم مواعيد المعاينة';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'appointments-calendar';

    /** mine = مواعيدي فقط، all = كل المواعيد التي أراها */
    public string $scope = 'mine';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return SvAccess::isAdmin($user) || SvAccess::isGeo($user) || SvAccess::isEngineer($user);
    }

    public static function getNavigationBadge(): ?string
    {
        $n = static::baseQuery('mine')->whereDate('appointment_at', today())->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'مواعيد اليوم';
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->scope = SvAccess::isAdmin(auth()->user()) ? 'all' : 'mine';
    }

    protected static function baseQuery(string $scope): Builder
    {
        $user = auth()->user();
        $query = SiteValidityRequest::query()->visibleTo($user)->whereNotNull('appointment_at')
            ->whereNotIn('status', [SvStatus::REJECTED]);

        if ($scope === 'mine' && ! SvAccess::isAdmin($user)) {
            $id = $user->getKey();
            $query->where(function (Builder $q) use ($user, $id) {
                if (SvAccess::isGeo($user)) {
                    $q->orWhere('geo_user_id', $id)->orWhereNull('geo_user_id');
                }
                if (SvAccess::isEngineer($user)) {
                    $q->orWhere('engineer_user_id', $id)->orWhereNull('engineer_user_id');
                }
            });
        }

        return $query;
    }

    /** أحداث التقويم بين تاريخين (يستدعيها FullCalendar). */
    public function events(string $start, string $end): array
    {
        $user = auth()->user();
        $wf = app(WorkflowService::class);
        $from = Carbon::parse($start)->startOfDay();
        $to = Carbon::parse($end)->endOfDay();

        return static::baseQuery($this->scope)
            ->whereBetween('appointment_at', [$from, $to])
            ->with('serviceType')
            ->orderBy('appointment_at')
            ->limit(1000)
            ->get()
            ->map(function (SiteValidityRequest $r) use ($user, $wf) {
                $overdue = in_array($r->status, [SvStatus::APPOINTMENT_SET, SvStatus::APPROVED], true)
                    && $r->appointment_at->lt(now()->startOfDay());
                $color = SvRequestFilters::STATUS_HEX[$r->status] ?? '#78756A';

                return [
                    'id' => (string) $r->id,
                    'title' => $r->number . ' — ' . $r->applicant_name,
                    'start' => $r->appointment_at->toIso8601String(),
                    'end' => $r->appointment_at->copy()->addHour()->toIso8601String(),
                    'url' => RequestResource::getUrl('view', ['record' => $r]),
                    'backgroundColor' => $color,
                    'borderColor' => $overdue ? '#A63A32' : $color,
                    'classNames' => $overdue ? ['sv-ev-overdue'] : [],
                    'editable' => $wf->can($r, $user, 'set_appointment'),
                    'extendedProps' => [
                        'status' => SvRequestFilters::STATUS_SHORT[$r->status] ?? $r->status,
                        'place' => $r->locationLabel(),
                        'service' => $r->serviceType?->name,
                        'phone' => $r->phone,
                        'overdue' => $overdue,
                        'geoDone' => $r->geo_inspected_at !== null,
                        'engDone' => $r->engineer_inspected_at !== null,
                    ],
                ];
            })->all();
    }

    /** إعادة الجدولة بالسحب والإفلات. @return array{ok: bool, message: string} */
    public function reschedule(int $id, string $start): array
    {
        $r = static::baseQuery('all')->whereKey($id)->first();
        $user = auth()->user();
        $wf = app(WorkflowService::class);

        if (! $r || ! $wf->can($r, $user, 'set_appointment')) {
            return $this->fail('لا يمكنك تعديل موعد هذا الطلب في حالته الحالية.');
        }

        try {
            $at = Carbon::parse($start)->setTimezone(config('app.timezone'));
            $wf->setAppointment($r, $user, $at, 'تعديل الموعد من تقويم المواعيد');
        } catch (WorkflowException $e) {
            return $this->fail($e->getMessage());
        } catch (\Throwable) {
            return $this->fail('تعذر تعديل الموعد.');
        }

        Notification::make()->success()->title('تم تعديل موعد ' . $r->number)
            ->body($at->translatedFormat('l j F Y — h:i a'))->send();

        return ['ok' => true, 'message' => ''];
    }

    public function setScope(string $scope): void
    {
        $this->scope = in_array($scope, ['mine', 'all'], true) ? $scope : 'mine';
        $this->dispatch('sv-calendar-refetch');
    }

    /** @return array{ok: bool, message: string} */
    private function fail(string $message): array
    {
        Notification::make()->danger()->title('لم يتم تعديل الموعد')->body($message)->send();

        return ['ok' => false, 'message' => $message];
    }

    protected function getViewData(): array
    {
        $today = static::baseQuery($this->scope)->whereDate('appointment_at', today());

        return [
            'stats' => [
                'today' => (clone $today)->count(),
                'week' => static::baseQuery($this->scope)->whereBetween('appointment_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'overdue' => static::baseQuery($this->scope)->overdue()->count(),
            ],
            'canSeeAll' => ! SvAccess::isAdmin(auth()->user()),
            'statusHex' => array_intersect_key(SvRequestFilters::STATUS_HEX, array_flip([SvStatus::APPOINTMENT_SET, SvStatus::APPROVED, SvStatus::INSPECTED, SvStatus::ATTACHMENTS_UPLOADED, SvStatus::COMPLETED])),
            'statusShort' => SvRequestFilters::STATUS_SHORT,
        ];
    }
}
