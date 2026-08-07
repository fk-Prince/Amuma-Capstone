<?php

namespace App\Http\Resources;

use App\Models\Bed;
use App\Models\Booking;
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

            'beds' => $this->whenLoaded('beds', function () {

                return $this->beds->map(function ($bed) {

                    return [

                        'bed_id'  => $bed->bed_id,
                        'room_id' => $bed->room_id,
                        'bed_no'  => $bed->bed_no,
                        'status' => $bed->status,


                        'reserved_admission' => $bed->reservedAdmission ? [
                            'patient_admission_id' => $bed->reservedAdmission->patient_admission_id,
                            'bed_id' => $bed->reservedAdmission->bed_id,
                            'status' => $bed->reservedAdmission->status,
                            'note' => $bed->reservedAdmission->note,
                            'admitted_at' => $bed->reservedAdmission->admitted_at,
                            'end_date' =>  $bed->reservedAdmission->end_date,
                            'booking_reference_id' => $bed->reservedAdmission->bookings?->reference_id,
                            'patient' => $bed->reservedAdmission->patient
                                ? [
                                    'patient_id' => $bed->reservedAdmission->patient->patient_id,
                                    'first_name' =>   $bed->reservedAdmission->patient->first_name,
                                    'last_name' => $bed->reservedAdmission->patient->last_name,
                                    'gender' => $bed->reservedAdmission->patient->gender,
                                    'date_of_birth' => $bed->reservedAdmission->patient->date_of_birth,
                                    'blood_type' =>  $bed->reservedAdmission->patient->blood_type,
                                    'phone_number' =>  $bed->reservedAdmission->patient->phone_number,
                                    'citizenship' => $bed->reservedAdmission->patient->citizenship,
                                    'height' =>  $bed->reservedAdmission->patient->height,
                                    'weight' => $bed->reservedAdmission->patient->weight,
                                ]
                                : null,
                        ] : null,
                        'current_admission' => $bed->currentAdmission
                            ? [
                                'patient_admission_id' => $bed->currentAdmission->patient_admission_id,
                                'bed_id' => $bed->currentAdmission->bed_id,
                                'status' => $bed->currentAdmission->status,
                                'note' => $bed->currentAdmission->note,
                                'admitted_at' => $bed->currentAdmission->admitted_at,
                                'end_date' =>  $bed->currentAdmission->end_date,
                                'patient' => $bed->currentAdmission->patient
                                    ? [
                                        'patient_id' => $bed->currentAdmission->patient->patient_id,
                                        'first_name' =>   $bed->currentAdmission->patient->first_name,
                                        'last_name' => $bed->currentAdmission->patient->last_name,
                                        'gender' => $bed->currentAdmission->patient->gender,
                                        'date_of_birth' => $bed->currentAdmission->patient->date_of_birth,
                                        'blood_type' =>  $bed->currentAdmission->patient->blood_type,
                                        'phone_number' =>  $bed->currentAdmission->patient->phone_number,
                                        'citizenship' => $bed->currentAdmission->patient->citizenship,
                                        'height' =>  $bed->currentAdmission->patient->height,
                                        'weight' => $bed->currentAdmission->patient->weight,
                                    ]
                                    : null,
                            ]
                            : null,
                    ];
                });
            }),
        ];
    }
}
