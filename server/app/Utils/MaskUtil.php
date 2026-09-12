<?php

namespace App\Utils;

class MaskUtil
{

    public static function accountDetails(string $method, string $accountDetails)
    {
        if (str_starts_with(strtolower(trim($method)), 'cash')) {
            return $accountDetails;
        }
        $length = strlen($accountDetails);

        if ($length <= 4) {
            return $accountDetails;
        }

        return substr($accountDetails, 0, 4)
            . str_repeat('X', $length - 8)
            . substr($accountDetails, -4);
    }

    public static function email(?string $email)
    {
        if (!$email || !str_contains($email, '@')) {
            return $email;
        }

        [$name, $domain] = explode('@', $email, 2);

        $visible = mb_strlen($name) <= 2
            ? mb_substr($name, 0, 1)
            : mb_substr($name, 0, 2);

        return $visible
            . str_repeat('•', max(3, mb_strlen($name) - mb_strlen($visible)))
            . '@' . $domain;
    }
}
