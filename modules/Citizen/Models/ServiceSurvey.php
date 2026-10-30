<?php

namespace Modules\Citizen\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_name', 'name', 'phone', 'age_group', 'gender',
        'q1_1_accessibility', 'q1_2_procedure_clarity', 'q1_3_needs_fulfillment',
        'q1_4_guidance', 'q1_5_staff_cooperation', 'q1_6_process_handling',
        'q2_1_service_speed', 'q2_2_wait_time', 'q2_3_delay_justification',
        'q3_1_staff_treatment', 'q3_2_problem_solving', 'q3_3_communication_ease',
        'q3_4_fees_clarity',
        'q4_1_cleanliness', 'q4_2_seating_comfort', 'q4_3_accessibility_tools',
        'suggestions', 'complaint_employee_name', 'complaint_reason',
        'is_reviewed',
    ];

    protected $casts = [
        'q1_1_accessibility'       => 'integer',
        'q1_2_procedure_clarity'   => 'integer',
        'q1_3_needs_fulfillment'   => 'integer',
        'q1_4_guidance'            => 'integer',
        'q1_5_staff_cooperation'   => 'integer',
        'q1_6_process_handling'    => 'integer',
        'q2_1_service_speed'       => 'integer',
        'q2_2_wait_time'           => 'integer',
        'q2_3_delay_justification' => 'integer',
        'q3_1_staff_treatment'     => 'integer',
        'q3_2_problem_solving'     => 'integer',
        'q3_3_communication_ease'  => 'integer',
        'q3_4_fees_clarity'        => 'integer',
        'q4_1_cleanliness'         => 'integer',
        'q4_2_seating_comfort'     => 'integer',
        'q4_3_accessibility_tools' => 'integer',
        'is_reviewed'              => 'boolean',
    ];

    /** The factory stays in database/factories (legacy location, see docs/architecture.md). */
    protected static function newFactory()
    {
        return \Database\Factories\ServiceSurveyFactory::new();
    }
}
