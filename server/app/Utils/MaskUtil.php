<?php

namespace App\Utils;

class MaskUtil
{

    public static function accountDetails(string $method, string $accountDetails): string
    {
        // Only cash-in-hand methods ("Cash", "Cash Pickup") carry a name or a
        // note rather than an account number, so only those go unmasked. This
        // has to be a prefix test: a substring test also matches "GCash" and
        // would print the payer's full GCash number.
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
