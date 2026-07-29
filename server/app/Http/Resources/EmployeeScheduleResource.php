<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $branchInfo = $this->employeeBranch->first();

        return [
            'employee_id' => $this->employee_id,
            'uuid' => $this->users->uuid ?? null,
            'email' => $this->users->email ?? null,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name ?? null,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'avatar' => $this->avatar,
            'location' => $this->locations,
            'birth_date' => $this->birth_date,
            'phone_number' => $this->phone_number,
            'role_name' => $branchInfo?->role_name
                ? ucwords(str_replace('_', ' ', $branchInfo->role_name))
                : null,
            'assignment_type' => $branchInfo?->assignment_type,
            'formatted_assignment_type' => match ($branchInfo?->assignment_type) {
                'both' => 'Homecare + Inhouse Facility',
                'homecare' => 'Homecare',
                'facility' => 'Inhouse Facility',
                default => 'Not yet Assigned',
            },
            'status' => $this->status,
            'hired_date' => $this->created_at,
            'is_busy' => $this->is_busy,
            'is_assigned' => $this->is_assigned
        ];
    }
}
