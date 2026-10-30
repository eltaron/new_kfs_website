<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidNationalID implements Rule
{
    public function passes($attribute, $value)
    {
        // Basic validation: must be 14 digits and numeric
        return is_numeric($value) && strlen((string) $value) === 14;
        // You can add more complex checks here (birthdate, governorate code, etc.)
    }
    public function message()
    {
        return 'الرقم القومي غير صالح. يجب أن يتكون من 14 رقمًا.';
    }
}
