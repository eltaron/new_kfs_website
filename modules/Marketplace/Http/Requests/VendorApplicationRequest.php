<?php

namespace Modules\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class VendorApplicationRequest extends FormRequest
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
            'wallet_number' => $digits($this->input('wallet_number')),
            'disability_card_number' => $digits($this->input('disability_card_number')),
        ]);
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'disability_card_number' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-\/]+$/'],
            'disability_card' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'national_id' => ['required', 'digits:14', Rule::unique('market_vendors', 'national_id')
                ->whereNull('deleted_at')
                ->where(fn ($q) => $q->where('user_id', '!=', (string) $this->user()?->getKey()))],
            'phone' => ['required', 'regex:/^01[0125][0-9]{8}$/'],
            'center' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'payout_method' => ['required', Rule::in(['wallet', 'bank'])],
            'wallet_number' => ['required_if:payout_method,wallet', 'nullable', 'regex:/^01[0125][0-9]{8}$/'],
            'bank_name' => ['required_if:payout_method,bank', 'nullable', 'string', 'max:100'],
            'account_name' => ['required_if:payout_method,bank', 'nullable', 'string', 'max:120'],
            'account_number' => ['required_if:payout_method,bank', 'nullable', 'string', 'max:34', 'regex:/^[A-Za-z0-9 ]+$/'],
            'terms' => ['accepted'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $own = (string) ($this->user()?->national_id ?? '');
                if ($own !== '' && $this->input('national_id') !== $own) {
                    $validator->errors()->add('national_id', 'الرقم القومي يجب أن يطابق الرقم المسجل في حسابك.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'store_name' => 'اسم المتجر',
            'description' => 'وصف المتجر',
            'logo' => 'شعار المتجر',
            'disability_card_number' => 'رقم بطاقة الخدمات المتكاملة',
            'disability_card' => 'صورة بطاقة الخدمات المتكاملة',
            'national_id' => 'الرقم القومي',
            'phone' => 'رقم الهاتف',
            'center' => 'المركز',
            'address' => 'العنوان',
            'payout_method' => 'طريقة استلام الأرباح',
            'wallet_number' => 'رقم المحفظة',
            'bank_name' => 'اسم البنك',
            'account_name' => 'اسم صاحب الحساب',
            'account_number' => 'رقم الحساب / IBAN',
            'terms' => 'الموافقة على الشروط',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'رقم الهاتف يجب أن يكون رقم محمول مصري من 11 رقماً يبدأ بـ 01.',
            'wallet_number.regex' => 'رقم المحفظة يجب أن يكون رقم محمول مصري من 11 رقماً.',
            'national_id.unique' => 'يوجد متجر مسجل بهذا الرقم القومي بالفعل.',
            'terms.accepted' => 'يجب الموافقة على شروط البيع في المتجر.',
        ];
    }
}
