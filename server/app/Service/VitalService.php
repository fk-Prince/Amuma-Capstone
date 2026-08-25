<?php

namespace App\Service;

use App\Models\User;
use App\Models\Vital;
use App\Repository\PatientRepository;
use App\Utils\MedicationPresenter;
use Exception;

class VitalService
{
    public function __construct(private PatientRepository $patientRepository) {}


    public function listVitals(array $payload)
    {
        $patient = $this->patientRepository->findByFields([
            ['uuid', '=', $payload['patient_uuid']]
        ]);

        if (!$patient) {
            throw new Exception('Patient not found');
        }

        $vitals = Vital::where('patient_id', $patient->patient_id)
            ->orderByDesc('recorded_date')
            ->orderByDesc('recorded_time')
            ->paginate((int) ($payload['per_page'] ?? 10));

        return response()->json([
            'data' => collect($vitals->items())
                ->map(fn($vital) => MedicationPresenter::vital($vital))
                ->values(),
            'meta' => [
                'current_page' => $vitals->currentPage(),
                'last_page' => $vitals->lastPage(),
                'total' => $vitals->total(),
                'per_page' => $vitals->perPage(),
            ],
        ], 200);
    }

    public function createVital(User $user, array $payload)
    {
        $patient = $this->patientRepository->findByFields([
            ['uuid', '=', $payload['patient_uuid']]
        ]);

        if (!$patient) {
            throw new Exception('Patient not found');
        }

        $data = $payload['payload'] ?? [];

        $vital = Vital::create([
            'patient_id' => $patient->patient_id,
            'blood_pressure_systolic' => $data['bloodPressureSystolic'] ?? null,
            'blood_pressure_diastolic' => $data['bloodPressureDiastolic'] ?? null,
            'heart_rate' => $data['heartRate'] ?? null,
            'respiratory_rate' => $data['respiratoryRate'] ?? null,
            'temperature' => $data['temperature'] ?? null,
            'oxygen_saturation' => $data['oxygenSaturation'] ?? null,
            'blood_glucose' => $data['bloodGlucose'] ?? null,
            'pain_level' => $data['painLevel'] ?? null,
            'recorded_date' => $data['recordedDate'],
            'recorded_time' => $data['recordedTime'],
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Successfully saved Vital Signs.',
            'data' => MedicationPresenter::vital($vital),
        ], 200);
    }

    public function updateVital(User $user, array $payload, string $id)
    {
        $vital = Vital::whereHas(
            'patient',
            fn($query) => $query->where('uuid', $payload['patient_uuid'])
        )->find($id);

        if (!$vital) {
            throw new Exception('Vital record not found');
        }

        $data = $payload['payload'] ?? [];

        $vital->update([
            'blood_pressure_systolic' => $data['bloodPressureSystolic'] ?? $vital->blood_pressure_systolic,
            'blood_pressure_diastolic' => $data['bloodPressureDiastolic'] ?? $vital->blood_pressure_diastolic,
            'heart_rate' => $data['heartRate'] ?? $vital->heart_rate,
            'respiratory_rate' => $data['respiratoryRate'] ?? $vital->respiratory_rate,
            'temperature' => $data['temperature'] ?? $vital->temperature,
            'oxygen_saturation' => $data['oxygenSaturation'] ?? $vital->oxygen_saturation,
            'blood_glucose' => $data['bloodGlucose'] ?? $vital->blood_glucose,
            'pain_level' => $data['painLevel'] ?? $vital->pain_level,
            'recorded_date' => $data['recordedDate'] ?? $vital->recorded_date,
            'recorded_time' => $data['recordedTime'] ?? $vital->recorded_time,
            'notes' => $data['notes'] ?? $vital->notes,
        ]);

        return response()->json([
            'message' => 'Successfully updated Vital Signs.',
            'data' => MedicationPresenter::vital($vital->fresh()),
        ], 200);
    }
}
