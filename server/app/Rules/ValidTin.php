<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTin implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !self::passes($value)) {
            $fail('The :attribute must be a valid TIN, e.g. 004-512-873-000.');
        }
    }

    public static function passes(string $value): bool
    {
        return (bool) preg_match('/^\d{3}-\d{3}-\d{3}-\d{3}$/', $value);
    }
}
