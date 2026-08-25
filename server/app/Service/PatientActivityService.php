<?php

namespace App\Service;

use App\Models\PatientActivity;
use App\Models\User;
use App\Repository\PatientRepository;
use App\Utils\PatientActivityPresenter;
use Exception;

class PatientActivityService
{
    public function __construct(private PatientRepository $patientRepository) {}

    public function listPatientActivities(array $payload)
    {
        $patient = $this->patientRepository->findByFields([
            ['uuid', '=', $payload['patient_uuid']]
        ]);

        if (!$patient) {
            throw new Exception('Patient not found');
        }

        $activities = PatientActivity::where('patient_id', $patient->patient_id)
            ->orderByDesc('occurred_at')
            ->paginate((int) ($payload['per_page'] ?? 10));

        return response()->json([
            'data' => collect($activities->items())
                ->map(fn($activity) => PatientActivityPresenter::patientActivity($activity))
                ->values(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
            ],
        ], 200);
    }

    public function createPatientActivity(User $user, array $payload)
    {
        $patient = $this->patientRepository->findByFields([
            ['uuid', '=', $payload['patient_uuid']]
        ]);

        if (!$patient) {
            throw new Exception('Patient not found');
        }

        $data = $payload['payload'] ?? [];

        $activity = PatientActivity::create([
            'patient_id' => $patient->patient_id,
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? null,
            'occurred_at' => $data['occurredAt'] ?? null,
        ]);

        return response()->json([
            'message' => 'Successfully added activity.',
            'data' => PatientActivityPresenter::patientActivity($activity),
        ], 200);
    }

    public function updatePatientActivity(User $user, array $payload, string $id)
    {
        $activity = PatientActivity::whereHas(
            'patient',
            fn($query) => $query->where('uuid', $payload['patient_uuid'])
        )->find($id);

        if (!$activity) {
            throw new Exception('Activity not found');
        }

        $data = $payload['payload'] ?? [];

        $activity->update([
            'title' => $data['title'] ?? $activity->title,
            'subtitle' => $data['subtitle'] ?? $activity->subtitle,
            'description' => $data['description'] ?? $activity->description,
            'type' => $data['type'] ?? $activity->type,
            'occurred_at' => $data['occurredAt'] ?? $activity->occurred_at,
        ]);

        return response()->json([
            'message' => 'Successfully updated activity.',
            'data' => PatientActivityPresenter::patientActivity($activity->fresh()),
        ], 200);
    }
}
