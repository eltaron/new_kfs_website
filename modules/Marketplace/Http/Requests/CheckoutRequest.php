<?php

namespace Modules\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Marketplace\Models\MarketOrder;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('shipping_phone');
        if (is_string($phone)) {
            $phone = strtr(trim($phone), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        }
        $this->merge([
            'shipping_phone' => $phone,
            'payment_method' => $this->input('payment_method', MarketOrder::METHOD_EFINANCE),
        ]);
    }

    public function rules(): array
    {
        return [
            'shipping_name' => ['required', 'string', 'min:3', 'max:120'],
            'shipping_phone' => ['required', 'regex:/^01[0125][0-9]{8}$/'],
            'shipping_center' => ['required', 'string', 'max:100'],
            'shipping_address' => ['required', 'string', 'min:5', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in(array_keys(MarketOrder::METHODS))],
        ];
    }

    public function attributes(): array
    {
        return [
            'shipping_name' => 'اسم المستلم',
            'shipping_phone' => 'رقم الهاتف',
            'shipping_center' => 'المركز',
            'shipping_address' => 'العنوان بالتفصيل',
            'notes' => 'ملاحظات',
            'payment_method' => 'طريقة الدفع',
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_phone.regex' => 'رقم الهاتف يجب أن يكون رقم محمول مصري من 11 رقماً يبدأ بـ 01.',
        ];
    }
}
