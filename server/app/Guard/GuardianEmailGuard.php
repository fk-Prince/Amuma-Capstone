<?php

namespace App\Guard;

use App\Enums\RoleEnum;
use App\Models\User;
use Exception;
use Illuminate\Support\Str;

class GuardianEmailGuard
{

    public static function assertAllowed(?string $email): void
    {
        $email = Str::lower(trim((string) $email));

        if ($email === '') {
            return;
        }

        $employee = User::with('employee.employeeBranch')
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first()
            ?->employee;

        if (!$employee) {
            return;
        }

        $isOwner = $employee->employeeBranch
            ->contains('role_name', RoleEnum::AgencyOwner->value);

        if (!$isOwner) {
            throw new Exception(
                "This email belongs to a staff member and can't be used for a guardian.",
                422
            );
        }
    }
}
