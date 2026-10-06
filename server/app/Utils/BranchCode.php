<?php

namespace App\Utils;

class BranchCode
{
    private const SEQUENCE_LENGTH = 6;

    public static function make(string $model, string $prefix, string $column, mixed $branchId): string
    {
        $stem = $prefix . str_pad((string) (int) $branchId, 2, '0', STR_PAD_LEFT) . '-';

        $last = $model::query()
            ->where($column, 'like', $stem . '%')
            ->orderByRaw("length({$column}) desc")
            ->orderByDesc($column)
            ->lockForUpdate()
            ->value($column);

        $next = $last ? ((int) substr($last, strlen($stem))) + 1 : 1;

        return $stem . str_pad((string) $next, self::SEQUENCE_LENGTH, '0', STR_PAD_LEFT);
    }
}
