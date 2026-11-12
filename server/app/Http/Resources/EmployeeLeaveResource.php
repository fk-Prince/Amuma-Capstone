<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeLeaveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'reason' => $this->reason,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'returned_at' => $this->returned_at?->toIso8601String(),
            'recorded_by' => $this->personName($this->recorder),
            'returned_by' => $this->personName($this->returner),
        ];
    }

    private function personName($user): ?string
    {
        $employee = $user?->employee;

        return $employee ? trim("{$employee->first_name} {$employee->last_name}") : null;
    }
}
