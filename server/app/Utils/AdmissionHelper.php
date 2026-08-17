<?php

namespace App\Utils;

use Carbon\Carbon;
use Exception;

class AdmissionHelper
{
    public function __construct() {}




    public static function calculateEndDate(Carbon $admissionDate, string $billingCycle)
    {
        return match (strtolower($billingCycle)) {
            'monthly' => $admissionDate->copy()->addMonth(),
            'yearly',
            'annual' => $admissionDate->copy()->addYear(),
            default => throw new Exception('Invalid billing cycle.', 422),
        };
    }

    public static function billingCycle(string $billingCycle): int
    {
        return match (strtolower(trim($billingCycle))) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi annual',
            'semi-annually',
            'semiannual' => 6,
            'annual',
            'yearly' => 12,
            default => throw new Exception('Invalid billing cycle.'),
        };
    }
}
