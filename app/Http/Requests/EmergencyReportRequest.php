<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmergencyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reporter_name'        => 'required|string|max:255',
            'reporter_phone'       => 'required|string|max:20',
            'reporter_national_id' => 'required|string|size:14',
            'report_type'          => 'required|string|max:255',
            'location_type'        => 'required|string|in:مدينة,قرية',
            'center'               => 'required|string|max:255',
            'area'                 => 'required|string|max:255',
            'location_description' => 'required|string|max:5000',
            'latitude'             => 'nullable|numeric|between:-90,90',
            'longitude'            => 'nullable|numeric|between:-180,180',
            'details'              => 'nullable|string|max:10000',
            'attachments'          => 'nullable|array',
            'attachments.*'        => 'file|mimes:jpg,jpeg,png,pdf,mp4|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'reporter_name.required'        => 'اسم مقدم البلاغ مطلوب.',
            'reporter_phone.required'       => 'رقم الهاتف مطلوب.',
            'reporter_national_id.required' => 'الرقم القومي مطلوب.',
            'reporter_national_id.size'     => 'الرقم القومي يجب أن يكون 14 رقمًا.',
            'report_type.required'          => 'نوع البلاغ مطلوب.',
            'location_type.required'        => 'يرجى اختيار نوع المكان.',
            'center.required'               => 'المركز مطلوب.',
            'area.required'                 => 'القرية أو المدينة مطلوبة.',
            'location_description.required' => 'وصف مكان الحادث مطلوب.',
        ];
    }
}
