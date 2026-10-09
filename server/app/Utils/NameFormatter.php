<?php

namespace App\Utils;

class NameFormatter
{
    public static function suffix(?string $value): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value));

        if ($value === '') {
            return null;
        }

        return preg_match('/^[ivxIVX]+\.?$/', $value)
            ? strtoupper($value)
            : self::capitalize($value);
    }

    public static function capitalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value));

        return preg_replace_callback(
            '/(^|[\s\-\'’.])(\p{Ll})/u',
            fn(array $match) => $match[1] . mb_strtoupper($match[2]),
            $value
        );
    }
}
