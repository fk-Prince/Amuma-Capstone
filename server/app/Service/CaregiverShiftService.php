<?php

namespace App\Service;

use App\Http\Resources\CaregiverShiftResource;
use App\Models\PatientAdmission;
use App\Repository\CaregiverShiftRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class CaregiverShiftService
{
    public const MAX_RESIDENTS = 3;
    public const MAX_DUTY_HOURS = 12;

    public function __construct(private CaregiverShiftRepository $shifts) {}

    public function list(array $payload)
    {
        $admission = $this->admission($payload);

        return response()->json([
            'data' => [
                'shifts' => CaregiverShiftResource::collection(
                    $this->shifts->forAdmission($admission->patient_admission_id)
                ),
                'caregivers' => $this->caregiverOptions((int) $payload['branch_id']),
                'limit' => self::MAX_RESIDENTS,
                'duty_limit' => self::MAX_DUTY_HOURS,
            ],
        ]);
    }

    public function assign(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $admission = $this->admission($payload);

            $this->requireCurrent($admission);

            $caregiverId = (int) $payload['caregiver_id'];

            if (!$this->shifts->isFacilityCaregiver($caregiverId, (int) $payload['branch_id'])) {
                throw new Exception('This employee is not an in-house facility caregiver.', 422);
            }

            if ($this->shifts->hasActiveAssignment($admission->patient_admission_id, $caregiverId)) {
                throw new Exception('This caregiver is already assigned to this resident.', 422);
            }

            $shift = $this->shifts->create([
                'caregiver_id' => $caregiverId,
                'admission_id' => $admission->patient_admission_id,
                'note' => $this->note($payload['note'] ?? null),
                'start_time' => $payload['start_time'],
                'end_time' => $payload['end_time'],
                'is_active' => true,
            ]);

            return response()->json([
                'message' => 'Caregiver assigned successfully.',
                'data' => $this->outcome($shift, (int) $payload['branch_id']),
            ], 201);
        });
    }

    public function update(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $shift = $this->shifts->find((int) $payload['id']);

            if (!$shift || (int) $shift->admission?->patient?->branch_id !== (int) $payload['branch_id']) {
                throw new Exception('Caregiver assignment not found.', 404);
            }

            $reactivating = array_key_exists('is_active', $payload)
                && (bool) $payload['is_active']
                && !$shift->is_active;

            if ($reactivating) {
                $this->requireCurrent($shift->admission);

                if ($this->shifts->hasActiveAssignment($shift->admission_id, $shift->caregiver_id, $shift->caregiver_facility_shift_id)) {
                    throw new Exception('This caregiver is already assigned to this resident.', 422);
                }
            }

            $shift->update(array_filter([
                'is_active' => array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : null,
                'start_time' => $payload['start_time'] ?? null,
                'end_time' => $payload['end_time'] ?? null,
            ], fn($value) => $value !== null) + (
                array_key_exists('note', $payload) ? ['note' => $this->note($payload['note'])] : []
            ));

            $message = match (true) {
                $reactivating => 'Caregiver assigned again.',
                array_key_exists('is_active', $payload) && !$shift->is_active => 'Caregiver unassigned.',
                default => 'Caregiver assignment updated.',
            };

            return response()->json([
                'message' => $message,
                'data' => $this->outcome($shift->fresh('caregiver'), (int) $payload['branch_id']),
            ]);
        });
    }

    private function outcome($shift, int $branchId): array
    {
        $option = collect($this->caregiverOptions($branchId))
            ->firstWhere('employee_id', $shift->caregiver_id);

        return [
            'shift' => new CaregiverShiftResource($shift),
            'caregiver' => $option ?? [
                'employee_id' => $shift->caregiver_id,
                'active_residents' => 0,
                'over_limit' => false,
                'duty_windows' => [],
                'duty_hours' => 0,
            ],
            'active_count' => $this->shifts->activeCountForAdmission($shift->admission_id),
        ];
    }

    private function caregiverOptions(int $branchId): array
    {
        $caregivers = $this->shifts->facilityCaregivers($branchId);
        $ids = $caregivers->pluck('employee_id')->all();
        $counts = $this->shifts->residentCounts($ids);
        $windows = $this->shifts->dutyWindows($ids);

        return $caregivers->map(function ($caregiver) use ($counts, $windows) {
            $residents = $counts[$caregiver->employee_id] ?? 0;
            $caregiverWindows = $windows[$caregiver->employee_id] ?? [];
            $dutyHours = round($this->dutyMinutes($caregiverWindows) / 60, 1);

            return [
                'employee_id' => $caregiver->employee_id,
                'full_name' => $caregiver->full_name,
                'avatar' => $caregiver->avatar,
                'phone_number' => $caregiver->phone_number,
                'active_residents' => $residents,
                'over_limit' => $residents > self::MAX_RESIDENTS,
                'duty_windows' => $caregiverWindows,
                'duty_hours' => $dutyHours,
            ];
        })->values()->all();
    }

    private function dutyMinutes(array $windows): int
    {
        $spans = [];

        foreach ($windows as $window) {
            $start = $this->minutes($window['start_time']);
            $end = $this->minutes($window['end_time']);

            if ($end > $start) {
                $spans[] = [$start, $end];
            } else {
                $spans[] = [$start, 1440];
                $spans[] = [0, $end];
            }
        }

        usort($spans, fn($a, $b) => $a[0] <=> $b[0]);

        $total = 0;
        $reach = 0;

        foreach ($spans as [$start, $end]) {
            $from = max($start, $reach);

            if ($end > $from) {
                $total += $end - $from;
            }

            $reach = max($reach, $end);
        }

        return $total;
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $hours * 60 + $minutes;
    }

    private function admission(array $payload): PatientAdmission
    {
        $admission = $this->shifts->findAdmission((int) $payload['admission_id']);

        if (!$admission || (int) $admission->patient?->branch_id !== (int) $payload['branch_id']) {
            throw new Exception('Admission not found.', 404);
        }

        return $admission;
    }

    private function requireCurrent(PatientAdmission $admission): void
    {
        if (!in_array($admission->status, [PatientAdmission::STATUS_ADMITTED], true)) {
            throw new Exception('Caregivers can only be assigned to a current admission.', 422);
        }
    }

    private function note(mixed $note): ?string
    {
        return is_string($note) && trim($note) !== ''
            ? mb_substr(trim($note), 0, 255)
            : null;
    }
}
