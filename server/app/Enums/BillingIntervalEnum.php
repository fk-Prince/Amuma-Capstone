<?php

namespace App\Enums;

use Carbon\Carbon;
use DateTime;

enum BillingIntervalEnum: string
{
    case MONTHLY = 'MONTHLY';
    case YEARLY = 'YEARLY';

    public function addTo(DateTime $date)
    {
        $carbonDate = Carbon::parse($date);

        return match ($this) {
            self::MONTHLY => $carbonDate->copy()->addMonth(),
            self::YEARLY => $carbonDate->copy()->addYear(),
        };
    }

    public function totalAmount(float $amount)
    {
        return match ($this) {
            self::MONTHLY => $amount,
            self::YEARLY => $amount * 12,
        };
    }

    public function loadPrice()
    {
        return match ($this) {
            BillingIntervalEnum::YEARLY => 'yearly_price',
            BillingIntervalEnum::MONTHLY => 'monthly_price',
        };
    }

    public function loadPriceKey()
    {
        return match ($this) {
            BillingIntervalEnum::YEARLY  => 'yearlyPrice',
            BillingIntervalEnum::MONTHLY => 'monthlyPrice',
        };
    }
}
