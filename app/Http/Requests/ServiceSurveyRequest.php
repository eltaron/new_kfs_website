<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'center_name'              => 'required|string|max:255',
            'name'                     => 'nullable|string|max:255',
            'phone'                    => 'nullable|string|max:20',
            'age_group'                => 'required|string|max:255',
            'gender'                   => 'required|string|in:ذكر,أنثى',
            'q1_1_accessibility'       => 'required|integer|between:1,5',
            'q1_2_procedure_clarity'   => 'required|integer|between:1,5',
            'q1_3_needs_fulfillment'   => 'required|integer|between:1,5',
            'q1_4_guidance'            => 'required|integer|between:1,5',
            'q1_5_staff_cooperation'   => 'required|integer|between:1,5',
            'q1_6_process_handling'    => 'required|integer|between:1,5',
            'q2_1_service_speed'       => 'required|integer|between:1,5',
            'q2_2_wait_time'           => 'required|integer|between:1,5',
            'q2_3_delay_justification' => 'required|integer|between:1,5',
            'q3_1_staff_treatment'     => 'required|integer|between:1,5',
            'q3_2_problem_solving'     => 'required|integer|between:1,5',
            'q3_3_communication_ease'  => 'required|integer|between:1,5',
            'q3_4_fees_clarity'        => 'required|integer|between:1,5',
            'q4_1_cleanliness'         => 'required|integer|between:1,5',
            'q4_2_seating_comfort'     => 'required|integer|between:1,5',
            'q4_3_accessibility_tools' => 'required|integer|between:1,5',
            'suggestions'              => 'nullable|string|max:5000',
            'complaint_employee_name'  => 'nullable|string|max:255',
            'complaint_reason'         => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'center_name.required'     => 'يرجى اختيار المركز التكنولوجي.',
            'age_group.required'        => 'الفئة العمرية مطلوبة.',
            'gender.required'           => 'الجنس مطلوب.',
            'gender.in'                 => 'يرجى اختيار جنس صحيح.',
        ];
    }
}
