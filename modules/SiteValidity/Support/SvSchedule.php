<?php

namespace Modules\SiteValidity\Support;

use Carbon\Carbon;
use Modules\SiteValidity\Models\CentreSchedule;
use Modules\SiteValidity\Models\SiteValidityRequest;

/** المواعيد المتاحة لمعاينة طلب: أيام المركز المحددة في الجدول، خلال الأيام القادمة، بشرط ألا تكتمل سعة اليوم. */
final class SvSchedule
{
    public const HORIZON_DAYS = 30;

    public static function forCentre(?int $centreId): ?CentreSchedule
    {
        return $centreId ? CentreSchedule::query()->where('center_id', $centreId)->first() : null;
    }

    /** @return array<string, string> 'Y-m-d' => 'الأحد 2026-11-01 — 10:00 (متبقي 3)' */
    public static function availableDates(SiteValidityRequest $request, int $horizon = self::HORIZON_DAYS): array
    {
        $schedule = self::forCentre($request->center_id);
        if ($schedule === null || $schedule->days() === []) {
            return [];
        }

        $start = now()->addDay()->startOfDay();
        $end = $start->copy()->addDays($horizon);
        $booked = SiteValidityRequest::query()
            ->where('center_id', $request->center_id)
            ->whereKeyNot($request->getKey())
            ->whereNotIn('status', [SvStatus::COMPLETED, SvStatus::REJECTED])
            ->whereBetween('appointment_at', [$start, $end->copy()->endOfDay()])
            ->pluck('appointment_at')
            ->countBy(fn ($at) => Carbon::parse($at)->toDateString());

        $time = substr((string) $schedule->start_time, 0, 5);
        $current = $request->appointment_at?->toDateString();
        $out = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if (! in_array($day->dayOfWeek, $schedule->days(), true)) {
                continue;
            }
            $date = $day->toDateString();
            $left = $schedule->daily_capacity - (int) ($booked[$date] ?? 0);
            if ($left <= 0 || $date === $current) {
                continue;
            }
            $out[$date] = CentreSchedule::WEEKDAYS[$day->dayOfWeek] . ' ' . $date . ' — ' . $time . ' (متبقي ' . $left . ')';
        }

        return $out;
    }

    /** يحوّل يوماً مختاراً إلى موعد (ساعة بدء المعاينات للمركز) بعد التأكد أنه ما زال متاحاً. */
    public static function resolve(SiteValidityRequest $request, string $date): ?Carbon
    {
        if (! array_key_exists($date, self::availableDates($request))) {
            return null;
        }
        $time = (string) (self::forCentre($request->center_id)?->start_time ?? '10:00:00');

        return Carbon::parse($date . ' ' . $time);
    }
}
