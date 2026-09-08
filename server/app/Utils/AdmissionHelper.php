<?php

namespace App\Utils;

use App\Models\AdmissionPeriod;
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

    public static function billingCycle(string $billingCycle)
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

    public static function cycleDays(Carbon $start, string $billingCycle)
    {
        $cycleEnd = self::calculateEndDate($start->copy(), $billingCycle);

        return max(1, (int) ceil($start->diffInDays($cycleEnd)));
    }

    public static function dailyRate(string $billingCycle, float $price)
    {
        return $price / self::cycleDays(Carbon::today(), $billingCycle);
    }


    public static function periodConsumption(AdmissionPeriod $period): array
    {
        $totalDays = $period->totalDays();
        $consumedDays = $period->consumedDays();

        return [
            'total'     => $totalDays,
            'consumed'  => $consumedDays,
            'remaining' => $totalDays - $consumedDays,
            'ratio'     => $consumedDays / $totalDays,
        ];
    }
}
