<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuggestionRequest extends FormRequest
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
            'phone'       => 'nullable|string|max:20',
            'national_id' => 'required|string|size:14',
            'subject'     => 'required|string|max:255',
            'message'     => 'required|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'الاسم مطلوب.',
            'national_id.required' => 'الرقم القومي مطلوب.',
            'national_id.size'     => 'الرقم القومي يجب أن يكون 14 رقمًا.',
            'subject.required'     => 'عنوان المقترح مطلوب.',
            'message.required'     => 'نص المقترح مطلوب.',
        ];
    }
}
