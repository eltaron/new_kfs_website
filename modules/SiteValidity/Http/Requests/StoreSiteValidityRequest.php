<?php

namespace Modules\SiteValidity\Http\Requests;

use App\Rules\SafeUpload;
use App\Rules\ValidNationalID;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\SiteValidity\Models\SiteValidityRequest;

class StoreSiteValidityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $digits = fn ($v) => is_string($v) ? strtr(trim($v), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']) : $v;

        $this->merge([
            'national_id' => $digits($this->input('national_id')),
            'phone' => $digits($this->input('phone')),
        ]);
    }

    public function rules(): array
    {
        $upload = (array) config('site-validity.citizen_upload');
        $file = fn (string ...$presence) => [...$presence, 'file', 'max:' . (int) $upload['max_kb'], 'extensions:' . implode(',', $upload['extensions']), 'mimes:' . implode(',', $upload['extensions']), new SafeUpload()];
        $noHtml = 'not_regex:/<[^>]*>|javascript\s*:/i';
        $bounds = SiteValidityRequest::KFS_BOUNDS;

        return [
            'service_type_id' => ['required', 'integer', Rule::exists('sv_service_types', 'id')->where('is_active', true)],
            'construction_type' => ['required', Rule::in(array_keys(SiteValidityRequest::CONSTRUCTION_TYPES))],
            'applicant_capacity' => ['required', Rule::in(array_keys(SiteValidityRequest::CAPACITIES))],
            'applicant_name' => ['required', 'string', 'min:6', 'max:120', 'regex:/^[\p{L}\p{M}\s\.\-\']+$/u'],
            'national_id' => ['required', 'string', new ValidNationalID()],
            'phone' => ['required', 'string', 'regex:/^01[0125][0-9]{8}$/'],
            'center_id' => ['required', 'integer', Rule::exists('centers', 'id')->where('is_active', true)],
            'village_id' => ['required', 'integer', Rule::exists('city_villages', 'id')->where('is_active', true)->where('center_id', (int) $this->input('center_id'))],
            'address' => ['required', 'string', 'min:5', 'max:300', $noHtml],
            'notes' => ['nullable', 'string', 'max:1000', $noHtml],
            // موقع الأرض على الخريطة (اختياري) — داخل حدود المحافظة
            'latitude' => ['nullable', 'numeric', 'between:' . $bounds[0] . ',' . $bounds[1], 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:' . $bounds[2] . ',' . $bounds[3], 'required_with:latitude'],
            'sketch' => $file('required'),
            'contract' => $file('nullable'), // عقد الملكية اختياري
            'id_card' => $file('required'),
            'poa' => $file('nullable', 'required_if:applicant_capacity,' . SiteValidityRequest::CAPACITY_AGENT),
            'floors' => $file('nullable', 'required_if:construction_type,' . SiteValidityRequest::CONSTRUCTION_EXTENSION),
            'agree' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'service_type_id' => 'نوع الخدمة',
            'construction_type' => 'نوع الصلاحية',
            'applicant_capacity' => 'صفة مقدم الطلب',
            'applicant_name' => 'اسم مقدم الطلب',
            'national_id' => 'الرقم القومي',
            'phone' => 'رقم الهاتف',
            'center_id' => 'المركز',
            'village_id' => 'المدينة أو القرية',
            'address' => 'عنوان الموقع',
            'notes' => 'الملاحظات',
            'latitude' => 'موقع الأرض',
            'longitude' => 'موقع الأرض',
            'sketch' => 'الكروكي',
            'contract' => 'صورة عقد الملكية',
            'id_card' => 'صورة البطاقة',
            'poa' => 'صورة التوكيل',
            'floors' => 'ملف موقف الأدوار القائمة',
            'agree' => 'الإقرار',
        ];
    }

    public function messages(): array
    {
        return \Modules\SiteValidity\Support\ValidationMessages::all() + [
            'applicant_name.regex' => 'الاسم يجب أن يحتوي على حروف فقط.',
            'phone.regex' => 'رقم الهاتف يجب أن يكون رقم محمول مصري من 11 رقماً يبدأ بـ 01.',
            'address.not_regex' => 'العنوان يحتوي على رموز غير مسموح بها.',
            'notes.not_regex' => 'الملاحظات تحتوي على رموز غير مسموح بها.',
            'village_id.exists' => 'المدينة أو القرية المختارة لا تتبع المركز المحدد.',
            'agree.accepted' => 'يجب الإقرار بصحة البيانات.',
            'poa.required_if' => 'يجب إرفاق صورة التوكيل عند التقديم بصفة وكيل.',
            'floors.required_if' => 'يجب إرفاق ملف موقف الأدوار القائمة لطلب التعلية.',
        ];
    }
}
