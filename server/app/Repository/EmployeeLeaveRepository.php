<?php

namespace App\Repository;

use App\Models\EmployeeBranch;
use App\Models\EmployeeLeave;
use Carbon\Carbon;

class EmployeeLeaveRepository
{
    public function start(EmployeeBranch $employeeBranch, array $payload, ?int $userId): EmployeeLeave
    {
        return EmployeeLeave::create([
            'employee_branch_id' => $employeeBranch->employee_branch_id,
            'type' => $payload['type'],
            'reason' => $payload['reason'],
            'starts_on' => now()->toDateString(),
            'ends_on' => $payload['ends_on'] ?? null,
            'recorded_by' => $userId,
        ]);
    }

    public function closeOpen(EmployeeBranch $employeeBranch, ?int $userId, ?Carbon $at = null): int
    {
        return EmployeeLeave::where('employee_branch_id', $employeeBranch->employee_branch_id)
            ->whereNull('returned_at')
            ->update([
                'returned_at' => $at ?? now(),
                'returned_by' => $userId,
                'updated_at' => now(),
            ]);
    }

    public function history(EmployeeBranch $employeeBranch)
    {
        return EmployeeLeave::where('employee_branch_id', $employeeBranch->employee_branch_id)
            ->with([
                'recorder.employee:employee_id,user_id,first_name,last_name',
                'returner.employee:employee_id,user_id,first_name,last_name',
            ])
            ->orderByRaw('returned_at IS NOT NULL')
            ->orderByDesc('starts_on')
            ->orderByDesc('employee_leave_id')
            ->get();
    }

    public function due(Carbon $today)
    {
        return EmployeeLeave::whereNull('returned_at')
            ->whereNotNull('ends_on')
            ->whereDate('ends_on', '<=', $today)
            ->with('employeeBranch')
            ->get();
    }
}
