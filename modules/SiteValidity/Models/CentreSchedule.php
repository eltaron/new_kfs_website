<?php

namespace Modules\SiteValidity\Models;

use Illuminate\Database\Eloquent\Model;

/** أيام نزول المعاينة لمركز (يحددها المركز الجيومكاني من «أيام المعاينة للمراكز»). */
class CentreSchedule extends Model
{
    /** Carbon::dayOfWeek → الاسم. */
    public const WEEKDAYS = [6 => 'السبت', 0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة'];

    protected $table = 'sv_centre_schedules';

    protected $fillable = ['center_id', 'weekdays', 'start_time', 'daily_capacity', 'notes'];

    protected $casts = [
        'weekdays' => 'array',
        'daily_capacity' => 'integer',
    ];

    /** @return list<int> */
    public function days(): array
    {
        return array_values(array_map('intval', (array) $this->weekdays));
    }

    public function daysLabel(): string
    {
        $days = $this->days();

        return implode('، ', array_values(array_filter(self::WEEKDAYS, fn ($d) => in_array($d, $days, true), ARRAY_FILTER_USE_KEY))) ?: 'لم تُحدد';
    }
}
