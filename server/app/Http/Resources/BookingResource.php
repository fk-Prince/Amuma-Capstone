<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $this->loadMissing('patientsBooking');

        $data = is_string($this->booking_data)
            ? json_decode($this->booking_data, true)
            : $this->booking_data;

        return [
            'booking_id' => $this->booking_id,
            'reference_id' => $this->reference_id,
            'category' => $this->category,
            'booking_type' => $this->booking_type,
            'status' => $this->status,

            'facility' => $data['facility'] ?? null,
            'homecare' => $data['homecare'] ?? null,

            'patient' => [
                'uuid' => $this->patientsBooking->first()?->uuid,
                'first_name' => $data['patient']['first_name'] ?? null,
                'middle_name' => $data['patient']['middle_name'] ?? null,
                'last_name' => $data['patient']['last_name'] ?? null,
                'gender' => $data['patient']['gender'] ?? null,
                'citizenship' => $data['patient']['citizenship'] ?? null,
                'occupation' => $data['patient']['occupation'] ?? null,
                'date_of_birth' => $data['patient']['date_of_birth'] ?? null,
                'phone_number' => $data['patient']['phone_number'] ?? null,
                'marital_status' => $data['patient']['marital_status'] ?? null,
                'height' => $data['patient']['height'] ?? null,
                'weight' => $data['patient']['weight'] ?? null,
                'blood_type' => $data['patient']['blood_type'] ?? null,
                'address' => $data['patient']['address'] ?? null,
            ],
            'guardian' => [
                'first_name' => $data['guardian']['first_name'] ?? null,
                'middle_name' => $data['guardian']['middle_name'] ?? null,
                'last_name' => $data['guardian']['last_name'] ?? null,
                'phone_number' => $data['guardian']['phone_number'] ?? null,
                'email' => $data['guardian']['email'] ?? null,
                'relationship' => $data['guardian']['relationship'] ?? null,
                'occupation' => $data['guardian']['occupation'] ?? null,
                'address' => $data['guardian']['address'] ?? null,
            ],
            'assessment' => $data['assessment'] ?? null,
            'reserved' => $data['reserved'] ?? null,
            'client' => $this->user,
            'payment' => $data['payment'] ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
