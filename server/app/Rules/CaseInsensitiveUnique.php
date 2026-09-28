<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CaseInsensitiveUnique implements ValidationRule
{
    public function __construct(
        private string $table,
        private string $column,
        private mixed $ignoreValue = null,
        private string $ignoreColumn = 'id',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = DB::table($this->table)
            ->whereRaw("LOWER(TRIM({$this->column})) = ?", [Str::lower(trim((string) $value))])
            ->when(
                $this->ignoreValue,
                fn($query) => $query->where($this->ignoreColumn, '!=', $this->ignoreValue)
            )
            ->exists();

        if ($exists) {
            $fail("The {$attribute} has already been taken.");
        }
    }
}
