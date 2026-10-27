<?php

namespace App\Utils;

class Vat
{
    public static function portion(float $amount, float $rate): float
    {
        if ($amount <= 0 || $rate <= 0) {
            return 0.0;
        }

        return round($amount * $rate / (100 + $rate), 2);
    }
}
