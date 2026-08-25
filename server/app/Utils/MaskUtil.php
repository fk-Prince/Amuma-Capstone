<?php

namespace App\Utils;

class MaskUtil
{

    public static function accountDetails(string $method, string $accountDetails): string
    {
        if (stripos($method, 'Cash') !== false) {
            return $accountDetails;
        }

        $length = strlen($accountDetails);

        if ($length <= 4) {
            return $accountDetails;
        }

        return str_repeat('*', $length - 4) . substr($accountDetails, -4);
    }
}
