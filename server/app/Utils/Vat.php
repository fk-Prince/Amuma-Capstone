<?php

namespace App\Utils;

class Vat
{
    public const RATE = 12.0;

    public static function portion(float $amount, float $rate): float
    {
        if ($amount <= 0 || $rate <= 0) {
            return 0.0;
        }

        return round($amount * $rate / (100 + $rate), 2);
    }

    public static function exclusive(float $subtotal, float $rate = self::RATE): array
    {
        $subtotal = round($subtotal, 2);
        $vat = $subtotal > 0 && $rate > 0 ? round($subtotal * $rate / 100, 2) : 0.0;

        return [
            'subtotal' => $subtotal,
            'vat_rate' => $rate,
            'vat_amount' => $vat,
            'total_amount' => round($subtotal + $vat, 2),
        ];
    }
}
