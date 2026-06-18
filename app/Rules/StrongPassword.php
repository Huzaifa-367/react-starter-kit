<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Setting;

class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $minLength  = (int) Setting::get('password_min_length', 8);
        $reqUpper   = (bool) Setting::get('password_require_uppercase', false);
        $reqNumbers = (bool) Setting::get('password_require_numbers', false);
        $reqSymbols = (bool) Setting::get('password_require_symbols', false);

        if (strlen($value) < $minLength) {
            $fail("Password must be at least {$minLength} characters.");
        }
        if ($reqUpper && !preg_match('/[A-Z]/', $value)) {
            $fail('Password must contain at least one uppercase letter.');
        }
        if ($reqNumbers && !preg_match('/[0-9]/', $value)) {
            $fail('Password must contain at least one number.');
        }
        if ($reqSymbols && !preg_match('/[\W_]/', $value)) {
            $fail('Password must contain at least one special character.');
        }
    }
}
