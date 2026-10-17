<?php

namespace Database\Seeders\Concerns;

trait PricesPerBranch
{
    private const BRANCH_PRICE_FACTORS = [1.00, 0.92, 1.08, 1.15, 0.96, 1.04, 1.12, 0.88, 1.02, 1.10];

    protected function branchPrice(float $base, int $position, int $step = 50): float
    {
        $factor = self::BRANCH_PRICE_FACTORS[$position % count(self::BRANCH_PRICE_FACTORS)];

        return round($base * $factor / $step) * $step;
    }
}
