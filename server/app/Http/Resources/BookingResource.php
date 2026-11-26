<?php

namespace App\Http\Resources;

use App\Models\Patient;
use App\Service\Booking\BookingStaffService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $this->loadMissing('patientsBooking', 'reviewer');

        $data = is_string($this->booking_data)
            ? json_decode($this->booking_data, true)
            : $this->booking_data;

        $existingPatientId = $data['patient']['patient_id'] ?? null;

        $patientModel = $this->patientsBooking->first()
            ?? ($existingPatientId ? Patient::find($existingPatientId) : null);

        $patientModel?->loadMissing('assessments', 'diagnoses');

        $storedAssessments = $patientModel?->assessments
            ->sortByDesc('patient_assessment_id')
            ->map(fn($assessment) => [
                'uuid' => $assessment->uuid,
                'condition' => $assessment->condition,
                'mental_state' => $assessment->mental_state,
                'affect' => $assessment->affect,
                'behavior' => $assessment->behavior,
                'communication' => $assessment->communication,
                'speech' => $assessment->speech,
                'life_system_profile' => $assessment->life_system_profile,
            ])
            ->values()
            ->all() ?? [];

        $storedDiagnoses = $patientModel?->diagnoses
            ->sortByDesc('patient_diagnosis_id')
            ->map(fn($diagnosis) => [
                'uuid' => $diagnosis->uuid,
                'diagnosis' => $diagnosis->diagnosis,
                'diagnosis_date' => $diagnosis->diagnosis_date?->format('Y-m-d'),
                'diagnosis_notes' => $diagnosis->diagnosis_notes,
                'diagnosis_file' => $diagnosis->diagnosis_file,
                'diagnosis_file_name' => $diagnosis->diagnosis_file
                    ? basename(parse_url($diagnosis->diagnosis_file, PHP_URL_PATH) ?: $diagnosis->diagnosis_file)
                    : null,
            ])
            ->values()
            ->all() ?? [];

        $assessmentData = !empty($data['assessment']) ? $data['assessment'] : $storedAssessments;
        $diagnosisData = !empty($data['diagnoses']) ? $data['diagnoses'] : $storedDiagnoses;

        $patientUuid = $patientModel?->uuid;

        return [
            'booking_id' => $this->booking_id,
            'reference_id' => $this->reference_id,
            'category' => $this->category,
            'booking_type' => $this->booking_type,
            'valid_until' => $this->valid_until,
            'status' => $this->status,
            'reason' => $this->reason,
            'reviewed_by' => $this->reviewed_by_name,
            'reviewed_at' => $this->reviewed_by ? $this->updated_at : null,

            'facility' => $data['facility'] ?? null,
            'homecare' => $data['homecare'] ?? null,

            'patient' => [
                'uuid' => $patientUuid,
                'patient_code' => $patientModel?->patient_code,
                'patient_id' => $existingPatientId,
                'avatar' => $patientModel?->avatar_url ?? ($data['patient']['avatar'] ?? null),
                'first_name' => $data['patient']['first_name'] ?? null,
                'middle_name' => $data['patient']['middle_name'] ?? null,
                'last_name' => $data['patient']['last_name'] ?? null,
                'suffix' => $data['patient']['suffix'] ?? null,
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
                'allergies' => $data['patient']['allergies'] ?? null,
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
            'assessment' => $assessmentData,
            'diagnoses' => $diagnosisData,
            'reserved' => $data['reserved'] ?? null,
            'requested_staff' => $this->whenLoaded('staffRequests', fn() => $this->resource->requestedStaff()),
            'staff_availability' => $this->when(
                $this->relationLoaded('staffRequests') && !empty($data['homecare']),
                fn() => app(BookingStaffService::class)->availabilitySummary(
                    (int) $this->branch_id,
                    $data['homecare'],
                    $this->resource->requestedStaff()
                )
            ),
            'schedule_code' => $this->when(
                $this->relationLoaded('invoices'),
                fn() => $this->resource->scheduleCode()
            ),
            'is_processed' => $this->isProcessed(),
            'client' => $this->user,
            'payment' => $data['payment'] ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
