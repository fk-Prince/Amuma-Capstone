<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaregiverShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'caregiver_facility_shift_id' => $this->caregiver_facility_shift_id,
            'caregiver_id' => $this->caregiver_id,
            'admission_id' => $this->admission_id,
            'caregiver_name' => $this->caregiver?->full_name,
            'avatar' => $this->caregiver?->avatar,
            'phone_number' => $this->caregiver?->phone_number,
            'note' => $this->note,
            'start_time' => substr((string) $this->start_time, 0, 5),
            'end_time' => substr((string) $this->end_time, 0, 5),
            'is_active' => (bool) $this->is_active,
        ];
    }
}
