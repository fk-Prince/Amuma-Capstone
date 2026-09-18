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
        if (!preg_match('/^(\d{3})-(\d{3})-(\d{3})-\d{3}$/', $value, $m)) {
            return false;
        }

        [, $a, $b, $c] = $m;
        $base = $a . $b . $c;

        if (preg_match('/^(\d)\1{8}$/', $base)) {
            return false;
        }

        if (in_array($base, ['123456789', '987654321'], true)) {
            return false;
        }

        return !($a === $b && $b === $c);
    }
}
