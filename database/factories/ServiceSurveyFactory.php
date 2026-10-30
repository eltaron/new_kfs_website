<?php

namespace Database\Factories;

use Modules\Citizen\Models\ServiceSurvey;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceSurvey> — استبيان رضا المواطنين (درجات 1..5). */
class ServiceSurveyFactory extends Factory
{
    protected $model = ServiceSurvey::class;

    public const QUESTIONS = ['q1_1_accessibility', 'q1_2_procedure_clarity', 'q1_3_needs_fulfillment', 'q1_4_guidance', 'q1_5_staff_cooperation',
        'q1_6_process_handling', 'q2_1_service_speed', 'q2_2_wait_time', 'q2_3_delay_justification', 'q3_1_staff_treatment',
        'q3_2_problem_solving', 'q3_3_communication_ease', 'q3_4_fees_clarity', 'q4_1_cleanliness', 'q4_2_seating_comfort', 'q4_3_accessibility_tools'];

    public function definition(): array
    {
        $mood = mt_rand(2, 5);
        $row = [
            'center_name' => 'المركز التكنولوجي - ' . DemoData::pick(DemoData::centerNames()),
            'name' => DemoData::chance(50) ? DemoData::name() : null,
            'phone' => DemoData::chance(50) ? DemoData::phone() : null,
            'age_group' => DemoData::pick(['18-30', '31-45', '46-60', '60+']),
            'gender' => DemoData::pick(['ذكر', 'أنثى']),
            'suggestions' => DemoData::chance(40) ? 'زيادة عدد الشبابيك وقت الذروة.' : null,
            'complaint_employee_name' => null,
            'complaint_reason' => null,
            'is_reviewed' => DemoData::chance(50),
            'created_at' => DemoData::pastDate(90),
        ];
        foreach (self::QUESTIONS as $q) {
            $row[$q] = max(1, min(5, $mood + mt_rand(-1, 1)));
        }

        return $row;
    }
}
