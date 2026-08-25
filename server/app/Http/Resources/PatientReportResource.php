<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sections = $this->resource['sections'] ?? [];
        $patient = $this->resource['patient'];

        $payload = [
            'patient' => [
                'patient_uuid' => $patient->uuid,
                'full_name' => trim(collect([
                    $patient->first_name,
                    $patient->middle_name,
                    $patient->last_name,
                ])->filter()->implode(' ')),
                'first_name' => $patient->first_name,
                'middle_name' => $patient->middle_name,
                'last_name' => $patient->last_name,
                'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
                'gender' => $patient->gender,
                'phone_number' => $this->localPhone($patient->phone_number),
                'citizenship' => $patient->citizenship,
                'address' => $patient->location?->full_address,
                'blood_type' => $patient->blood_type,
                'height' => $patient->height,
                'weight' => $patient->weight,
                'allergies' => $patient->allergies,
                'branch_name' => $patient->branch?->name,
            ],
            'generated_at' => now()->toIso8601String(),
            'sections' => array_keys($sections),
        ];

        foreach ($sections as $key => $rows) {
            $payload[$key] = $rows;
        }

        return $payload;
    }

    private function localPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $trimmed = trim($phone);
        $digits = preg_replace('/\D/', '', $trimmed);

        if (str_starts_with($digits, '63') && strlen($digits) >= 12) {
            $digits = '0' . substr($digits, 2);
        }

        if (strlen($digits) === 10 && !str_starts_with($digits, '0')) {
            $digits = '0' . $digits;
        }

        if (strlen($digits) === 11) {
            return substr($digits, 0, 4) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7);
        }

        return $digits ?: null;
    }
}
