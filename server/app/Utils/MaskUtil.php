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

        return str_repeat('*', $length - 4) . substr($accountDetails, -4);
    }
}
