<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'required|string|max:20',
            'national_id' => 'required|string|size:14',
            'subject'     => 'required|string|max:255',
            'message'     => 'required|string|max:5000',
            'attachment'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'الاسم مطلوب.',
            'phone.required'       => 'رقم الهاتف مطلوب.',
            'national_id.required' => 'الرقم القومي مطلوب.',
            'national_id.size'     => 'الرقم القومي يجب أن يكون 14 رقمًا.',
            'subject.required'     => 'موضوع الشكوى مطلوب.',
            'message.required'     => 'نص الشكوى مطلوب.',
            'message.max'          => 'نص الشكوى لا يتجاوز 5000 حرف.',
            'attachment.mimes'     => 'المرفق يجب أن يكون PDF أو JPG أو PNG.',
            'attachment.max'       => 'حجم المرفق لا يتجاوز 2 ميجابايت.',
        ];
    }
}
