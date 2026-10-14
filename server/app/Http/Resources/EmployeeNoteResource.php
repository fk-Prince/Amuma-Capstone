<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'note' => $this->note,
            'employee_uuid' => $this->employee?->users?->uuid,
            'employee_name' => $this->employee?->full_name,
            'patient_uuid' => $this->patient?->uuid,
            'patient_name' => $this->patient?->display_name,
            'schedule_code' => $this->schedule?->schedule_code,
            'scheduled_at' => $this->schedule?->scheduled_at?->toISOString(),
            'recorded_by' => $this->recorder?->full_name,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
