<?php

namespace App\Models\Concerns;

use App\Utils\NameFormatter;

trait CapitalizesNames
{
    public function setFirstNameAttribute(?string $value): void
    {
        $this->attributes['first_name'] = NameFormatter::capitalize($value);
    }

    public function setMiddleNameAttribute(?string $value): void
    {
        $this->attributes['middle_name'] = NameFormatter::capitalize($value);
    }

    public function setLastNameAttribute(?string $value): void
    {
        $this->attributes['last_name'] = NameFormatter::capitalize($value);
    }
}
