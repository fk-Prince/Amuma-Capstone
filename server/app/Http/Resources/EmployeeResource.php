<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $branchId = $request->branch_id;

        $employeeBranch = $this->employee?->employeeBranch
            ?->firstWhere('branch_id', $branchId);


        return [
            'uuid' => $this->uuid,
            'employee_code' => $this->employee?->employee_code,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'avatar' => $this->avatar,
            'documents' => $this->employee?->documents ?? [],
            'achievements' => $this->employee?->achievements ?? [],
            'birth_date' => $this->employee->birth_date,
            'location' => $this->employee->locations,
            'role_name' => ucwords(str_replace('_', ' ', $employeeBranch?->role_name)),
            'assignment_type' => $employeeBranch?->assignment_type,
            'phone_number' => $this->employee?->phone_number,
            'status' => $employeeBranch?->status,
            'leave' => $employeeBranch?->status === 'on_leave' && $employeeBranch->relationLoaded('currentLeave') && $employeeBranch->currentLeave
                ? [
                    'type' => $employeeBranch->currentLeave->type,
                    'starts_on' => $employeeBranch->currentLeave->starts_on?->toDateString(),
                    'ends_on' => $employeeBranch->currentLeave->ends_on?->toDateString(),
                ]
                : null,
            'hired_date' => $this->employee->created_at,
            'permissions' => $this->employee?->permissions
                ->map(fn($permission) => [
                    'module_name' => $permission->modules?->module_name,
                    'actions' => $permission->grantedActions(),
                ])
                ->filter(fn($permission) => $permission['actions'])
                ->values(),
        ];
    }
}
