<?php

namespace Modules\SiteValidity\database\seeders;

use Illuminate\Database\Seeder;
use Modules\SiteValidity\Models\CentreSchedule;
use Modules\SiteValidity\Models\Geo\Centre;

/**
 * بيانات مؤقتة لأيام المعاينة: يومان أو ثلاثة لكل مركز بالتناوب (لا تمس مركزاً له أيام محددة بالفعل).
 * php artisan db:seed --class="Modules\SiteValidity\database\seeders\SiteValidityScheduleSeeder"
 */
class SiteValidityScheduleSeeder extends Seeder
{
    /** أنماط أيام (Carbon::dayOfWeek: 0 الأحد ... 6 السبت) تُوزع على المراكز بالتناوب. */
    private const PATTERNS = [
        [0, 2],      // الأحد، الثلاثاء
        [1, 3],      // الاثنين، الأربعاء
        [6, 1, 4],   // السبت، الاثنين، الخميس
        [0, 3],      // الأحد، الأربعاء
        [2, 4],      // الثلاثاء، الخميس
        [6, 2],      // السبت، الثلاثاء
    ];

    private const TIMES = ['09:00', '10:00', '11:00'];

    public function run(): void
    {
        $created = 0;
        foreach (Centre::query()->active()->get(['id'])->values() as $i => $centre) {
            $schedule = CentreSchedule::query()->firstOrCreate(['center_id' => $centre->id], [
                'weekdays' => self::PATTERNS[$i % count(self::PATTERNS)],
                'start_time' => self::TIMES[$i % count(self::TIMES)],
                'daily_capacity' => [6, 8, 10][$i % 3],
                'notes' => 'بيانات تجريبية — يعدلها المركز الجيومكاني.',
            ]);
            $created += (int) $schedule->wasRecentlyCreated;
        }

        $this->command?->info("أيام المعاينة: أُضيفت مواعيد تجريبية لـ {$created} مركز.");
    }
}
