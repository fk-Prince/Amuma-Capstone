<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'room_id'   => $this->room_id,
            'room_no'   => $this->room_no,
            'floor'     => $this->floor,
            'branch_id' => $this->branch_id,
            'room_type' => $this->room_type,
            'capacity'  => $this->capacity,
            'status'    => $this->status,

            'beds' => $this->whenLoaded('beds', fn() => $this->beds->map(
                fn($bed) => [
                    'bed_id'  => $bed->bed_id,
                    'room_id' => $bed->room_id,
                    'status'  => $bed->status,
                    'bed_no'  => $bed->bed_no,

                    'current_admission' => $bed->currentAdmission
                        ? [
                            'patient_admission_id' => $bed->currentAdmission->patient_admission_id,
                            'bed_id'               => $bed->currentAdmission->bed_id,
                            'status'               => $bed->currentAdmission->status,
                            'note'                 => $bed->currentAdmission->note,
                            'admitted_at'          => $bed->currentAdmission->admitted_at,
                            'end_date'             => $bed->currentAdmission->end_date,

                            'patient' => $bed->currentAdmission->patient
                                ? [
                                    'patient_id'    => $bed->currentAdmission->patient->patient_id,
                                    'first_name'    => $bed->currentAdmission->patient->first_name,
                                    'last_name'     => $bed->currentAdmission->patient->last_name,
                                    'gender'        => $bed->currentAdmission->patient->gender,
                                    'date_of_birth' => $bed->currentAdmission->patient->date_of_birth,
                                    'blood_type'    => $bed->currentAdmission->patient->blood_type,
                                    'phone_number'  => $bed->currentAdmission->patient->phone_number,
                                    'citizenship'   => $bed->currentAdmission->patient->citizenship,
                                    'height'        => $bed->currentAdmission->patient->height,
                                    'weight'        => $bed->currentAdmission->patient->weight,
                                ]
                                : null,
                        ]
                        : null,
                ],
            )),
        ];
    }
}
