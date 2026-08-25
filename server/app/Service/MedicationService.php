<?php

namespace App\Service;

use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\User;
use App\Repository\PatientRepository;
use App\Utils\MedicationPresenter;
use Exception;

class MedicationService
{
    public function __construct(private PatientRepository $patientRepository) {}

    /**
     * Medications are no longer bundled into the main patient fetch — the
     * Medication tab pulls its own paginated page on demand instead.
     */
    public function listMedications(array $payload)
    {
        $patient = $this->patientRepository->findByFields([
            ['uuid', '=', $payload['patient_uuid']]
        ]);

        if (!$patient) {
            throw new Exception('Patient not found');
        }

        $medications = Medication::where('patient_id', $patient->patient_id)
            ->with('schedules')
            ->orderByDesc('recorded_at')
            ->paginate((int) ($payload['per_page'] ?? 10));

        return response()->json([
            'data' => collect($medications->items())
                ->map(fn($medication) => MedicationPresenter::medication($medication))
                ->values(),
            'meta' => [
                'current_page' => $medications->currentPage(),
                'last_page' => $medications->lastPage(),
                'total' => $medications->total(),
                'per_page' => $medications->perPage(),
            ],
        ], 200);
    }

    public function createMedication(User $user, array $payload)
    {
        $patient = $this->patientRepository->findByFields([
            ['uuid', '=', $payload['patient_uuid']]
        ]);

        if (!$patient) {
            throw new Exception('Patient not found');
        }

        $data = $payload['payload'] ?? [];

        $medication = Medication::create([
            'patient_id' => $patient->patient_id,
            'name' => $data['name'],
            'strength' => $data['strength'],
            'dosage_amount' => $data['dosageAmount'],
            'dosage_unit' => $data['dosageUnit'],
            'route' => $data['route'],
            'instructions' => $data['instructions'],
            'taken_for' => $data['takenFor'] ?? null,
            'duration' => $data['duration'],
            'frequency' => $data['frequency'] ?? 'everyday',
            'kind' => $data['kind'],
            'times' => $data['times'] ?? [],
            'start_date' => $data['startDate'],
            'recorded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Successfully saved Medication.',
            'data' => MedicationPresenter::medication($medication),
        ], 200);
    }

    public function updateMedication(User $user, array $payload, string $id)
    {
        $medication = Medication::whereHas(
            'patient',
            fn($query) => $query->where('uuid', $payload['patient_uuid'])
        )->find($id);

        if (!$medication) {
            throw new Exception('Medication record not found');
        }

        $data = $payload['payload'] ?? [];

        $medication->update(array_filter([
            'name' => $data['name'] ?? null,
            'strength' => $data['strength'] ?? null,
            'dosage_amount' => $data['dosageAmount'] ?? null,
            'dosage_unit' => $data['dosageUnit'] ?? null,
            'route' => $data['route'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'taken_for' => $data['takenFor'] ?? null,
            'duration' => $data['duration'] ?? null,
            'frequency' => $data['frequency'] ?? null,
            'kind' => $data['kind'] ?? null,
            'times' => $data['times'] ?? null,
            'start_date' => $data['startDate'] ?? null,
        ], fn($value) => $value !== null));

        return response()->json([
            'message' => 'Successfully updated Medication.',
            'data' => MedicationPresenter::medication($medication->fresh()),
        ], 200);
    }

    public function markDosage(User $user, array $payload)
    {
        $schedule = $payload['medSchedule'];

        $medication = Medication::whereHas(
            'patient',
            fn($query) => $query->where('uuid', $payload['patient_uuid'])
        )->find($schedule['medication_id']);

        if (!$medication) {
            throw new Exception('Medication record not found');
        }

        if ($schedule['status'] === 'removed') {
            $removed = $medication->schedules()
                ->where('medication_schedule_id', $schedule['schedule_id'] ?? null)
                ->first();

            $removed?->delete();

            return response()->json([
                'message' => 'Successfully updated dosage schedule.',
                'data' => $removed ? MedicationPresenter::schedule($removed) : null,
                'status' => $schedule['status'],
            ], 200);
        }

        $existing = $medication->schedules()
            ->where('date', $schedule['date'])
            ->where('time', $schedule['time'])
            ->first();

        $dose = $existing ?? $medication->schedules()->create([
            'date' => $schedule['date'],
            'time' => $schedule['time'],
            'status' => MedicationSchedule::STATUS_TAKEN,
            'marked_by' => $user->user_id,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Successfully updated dosage schedule.',
            'data' => MedicationPresenter::schedule($dose),
            'status' => $schedule['status'],
        ], 200);
    }

}
