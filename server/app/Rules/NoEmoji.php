<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoEmoji implements ValidationRule
{
    private const PATTERN = '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2300}-\x{23FF}\x{2B00}-\x{2BFF}\x{FE0F}\x{20E3}\x{200D}]/u';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && !self::passes($value)) {
            $fail('The :attribute cannot contain emoji.');
        }
    }

    public static function passes(string $value): bool
    {
        return !preg_match(self::PATTERN, $value);
    }
}
