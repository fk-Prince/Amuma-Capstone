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
        // return parent::toArray($request);

        return [
            'id' => $this->employees->employee_id,
            'first_name' => $this->employees->first_name,
            'last_name' => $this->employees->last_name,
            'status' => $this->employees->status, // TO BE CHANGeD
            'birth_date' => $this->employees->birth_date,
            'phone_number' => $this->employees->phone_number,
            'avatar' => $this->employees->avatar,
            'assignment_type' => match ($this->assignment_type) {
                'both' => 'Homecare + Inhouse Facility',
                'homecare' => 'Homecare',
                'facility' => 'Inhouse Facility',
                default => 'Not yet Assigned',
            },
            'role_name' => ucwords(str_replace('_', ' ', $this->role_name)),
            'hired_date' => $this->employees->created_at,
        ];
    }
}
