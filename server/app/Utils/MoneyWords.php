<?php

namespace App\Utils;

class MoneyWords
{
    private const ONES = [
        0 => 'zero',
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nine',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
        13 => 'thirteen',
        14 => 'fourteen',
        15 => 'fifteen',
        16 => 'sixteen',
        17 => 'seventeen',
        18 => 'eighteen',
        19 => 'nineteen',
    ];

    private const TENS = [
        2 => 'twenty',
        3 => 'thirty',
        4 => 'forty',
        5 => 'fifty',
        6 => 'sixty',
        7 => 'seventy',
        8 => 'eighty',
        9 => 'ninety',
    ];

    private const SCALES = [
        ['trillion', 1000000000000],
        ['billion', 1000000000],
        ['million', 1000000],
        ['thousand', 1000],
    ];


    public static function pesos(float $amount): string
    {
        $amount = round(max($amount, 0), 2);

        $whole = (int) floor($amount);
        $centavos = (int) round(($amount - $whole) * 100);

        $words = strtoupper(self::convert($whole))
            . ' PESO' . ($whole === 1 ? '' : 'S');

        if ($centavos > 0) {
            $words .= ' AND ' . strtoupper(self::convert($centavos))
                . ' CENTAVO' . ($centavos === 1 ? '' : 'S');
        }

        return $words . ' ONLY';
    }

    private static function convert(int $number): string
    {
        if ($number < 20) {
            return self::ONES[$number];
        }

        if ($number < 100) {
            $tens = self::TENS[intdiv($number, 10)];
            $remainder = $number % 10;

            return $remainder
                ? $tens . '-' . self::ONES[$remainder]
                : $tens;
        }

        if ($number < 1000) {
            $hundreds = self::ONES[intdiv($number, 100)] . ' hundred';
            $remainder = $number % 100;

            return $remainder
                ? $hundreds . ' ' . self::convert($remainder)
                : $hundreds;
        }

        foreach (self::SCALES as [$name, $value]) {
            if ($number >= $value) {
                $leading = self::convert(intdiv($number, $value)) . ' ' . $name;
                $remainder = $number % $value;

                return $remainder
                    ? $leading . ' ' . self::convert($remainder)
                    : $leading;
            }
        }

        return (string) $number;
    }
}
