<?php

namespace Modules\SiteValidity\Http\Requests;

use App\Rules\SafeUpload;
use Illuminate\Foundation\Http\FormRequest;

class SubmitCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $upload = (array) config('site-validity.citizen_upload');

        return [
            'reply' => ['required', 'string', 'min:3', 'max:3000', 'not_regex:/<[^>]*>|javascript\s*:/i'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['file', 'max:' . (int) $upload['max_kb'], 'extensions:' . implode(',', $upload['extensions']), 'mimes:' . implode(',', $upload['extensions']), new SafeUpload()],
        ];
    }

    public function attributes(): array
    {
        return ['reply' => 'رد الاستيفاء', 'files' => 'المستندات', 'files.*' => 'المستند'];
    }

    public function messages(): array
    {
        return \Modules\SiteValidity\Support\ValidationMessages::all() + ['reply.not_regex' => 'الرد يحتوي على رموز غير مسموح بها.'];
    }
}
