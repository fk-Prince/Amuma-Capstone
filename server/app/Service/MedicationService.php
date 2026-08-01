<?php

namespace App\Service;

use App\Models\User;
use App\Repository\PatientRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MedicationService
{
    public function __construct(private PatientRepository $patientRepository) {}

    public function createMedication(User $user, array $payload)
    {
        $patient = $this->patientRepository->findByFields([
            ['uuid', '=', $payload['patient_uuid']]
        ]);

        if (!$patient) {
            throw new \Exception('Patient not found');
        }

        $medications = $patient->medication ?? [];

        if ($payload['category'] === "dosage") {
            $schedule = $payload['medSchedule'];
            $updatedSchedule = null;
            foreach ($medications as &$medication) {
                if ($medication['id'] !== $schedule['medication_id']) {
                    continue;
                }
                if ($schedule['status'] === "taken") {
                    $medication['schedules'] ??= [];
                    $exists = collect($medication['schedules'])
                        ->contains(function ($item) use ($schedule) {
                            return $item['date'] === $schedule['date']
                                && $item['time'] === $schedule['time'];
                        });

                    if (!$exists) {
                        do {
                            $id = (string) Str::uuid();
                            $scheduleExists = collect($medications)
                                ->flatMap(function ($med) {
                                    return $med['schedules'] ?? [];
                                })
                                ->contains('id', $id);
                        } while ($scheduleExists);
                        $updatedSchedule = [
                            'id' => $id,
                            'date' => $schedule['date'],
                            'time' => $schedule['time'],
                            'status' => $schedule['status'],
                            'marked_by' => $user->uuid,
                            'recorded_date' => now()->toISOString(),
                        ];
                        $medication['schedules'][] = $updatedSchedule;
                    } else {
                        $updatedSchedule = collect($medication['schedules'])
                            ->first(function ($item) use ($schedule) {
                                return $item['date'] === $schedule['date']
                                    && $item['time'] === $schedule['time'];
                            });
                    }
                }
                if ($schedule['status'] === "removed") {
                    $removedSchedule = collect($medication['schedules'] ?? [])
                        ->first(function ($item) use ($schedule) {
                            return $item['id'] === ($schedule['schedule_id'] ?? null);
                        });


                    $medication['schedules'] = collect($medication['schedules'] ?? [])
                        ->reject(function ($item) use ($schedule) {
                            return $item['id'] === ($schedule['schedule_id'] ?? null);
                        })
                        ->values()
                        ->toArray();
                    $updatedSchedule = $removedSchedule;
                }
                break;
            }

            $patient->update([
                'medication' => $medications,
            ]);
            return response()->json([
                'message' => 'Successfully updated dosage schedule.',
                'data' => $updatedSchedule,
                'status' => $schedule['status'],
            ], 200);
        }

        do {
            $id = (string) Str::uuid();
        } while (collect($medications)->contains('id', $id));

        $newMedication = [
            'id' => $id,
            'category' => $payload['category'],
            'recorded_date' => now()->toISOString(),
            ...($payload['payload'] ?? []),
        ];

        $medications[] = $newMedication;

        $patient->update([
            'medication' => $medications,
        ]);

        return response()->json([
            'message' => 'Successfully saved ' . $payload['category'] . '.',
            'data' => $newMedication,
        ], 200);
    }

    public function updateMedication(User $user, array $payload, string $id)
    {
        $patient = $this->patientRepository->findByFields([
            ['uuid', '=', $payload['patient_uuid']]
        ]);

        if (!$patient) {
            throw new \Exception('Patient not found');
        }

        $medications = $patient->medication ?? [];

        $index = collect($medications)->search(function ($item) use ($id) {
            return $item['id'] === $id;
        });

        if ($index === false) {
            throw new \Exception('Medication record not found');
        }

        $medications[$index] = [
            ...$medications[$index],
            ...($payload['payload'] ?? []),
        ];

        $patient->update([
            'medication' => array_values($medications),
        ]);

        return response()->json([
            'message' => 'Successfully updated  ' . $medications[$index]['category'] . '.',
            'data' => $medications[$index],
        ], 200);
    }
    // public function updateMedication(User $user, array $payload, string $id)
    // {
    //     $patient = $this->patientRepository->findByFields([
    //         ['uuid', '=', $payload['patient_uuid']]
    //     ]);

    //     if (!$patient) {
    //         throw new \Exception('Patient not found');
    //     }

    //     $medications = $patient->medication ?? [];

    //     $index = collect($medications)->search(function ($item) use ($id) {
    //         return $item['id'] === $id;
    //     });

    //     if ($index === false) {
    //         throw new \Exception('Medication record not found');
    //     }

    //     $medications[$index] = [
    //         ...$medications[$index],
    //         'data' => collect($payload)
    //             ->except(['patient_id', 'id'])
    //             ->toArray(),
    //     ];

    //     $patient->update([
    //         'medication' => array_values($medications),
    //     ]);

    //     return response()->json([
    //         'message' => 'Successfully updated medication.',
    //         'data' => $medications[$index],
    //     ], 200);
    // }
}
