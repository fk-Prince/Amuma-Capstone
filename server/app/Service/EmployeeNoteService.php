<?php

namespace App\Service;

use App\Http\Resources\EmployeeNoteResource;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\User;
use App\Repository\EmployeeNoteRepository;
use Exception;

class EmployeeNoteService
{
    private const NOTE_MIN = 3;

    public function __construct(private EmployeeNoteRepository $notes) {}

    public function list(array $payload)
    {
        $branchId = (int) $payload['branch_id'];
        $perPage = (int) ($payload['per_page'] ?? 10);

        if (!empty($payload['schedule_code'])) {
            $schedule = $this->scheduleOrFail($payload['schedule_code'], $branchId);

            return response()->json([
                'data' => EmployeeNoteResource::collection(
                    $this->notes->forSchedule($branchId, $schedule->schedule_id)
                )->resolve(),
            ]);
        }

        if (!empty($payload['patient_uuid'])) {
            $patient = $this->patientOrFail($payload['patient_uuid'], $branchId);

            return $this->paginated($this->notes->paginateForPatient($branchId, $patient->patient_id, $perPage));
        }

        $employee = $this->employeeOrFail($payload['employee_uuid'] ?? '', $branchId);

        $page = $this->notes->paginateForEmployee($branchId, $employee->employee_id, $perPage);

        $response = $this->paginated($page)->getData(true);

        if ($page->currentPage() === 1) {
            $response['schedules'] = $this->notes
                ->schedulesOf($employee->employee_id, $branchId)
                ->map(fn(Schedule $schedule) => [
                    'schedule_code' => $schedule->schedule_code,
                    'scheduled_at' => $schedule->scheduled_at?->toISOString(),
                    'patient_name' => $schedule->patient?->display_name,
                ])
                ->values();

            $response['patients'] = $this->notes
                ->admittedPatients($branchId)
                ->map(fn(Patient $patient) => [
                    'patient_uuid' => $patient->uuid,
                    'patient_name' => $patient->display_name,
                ])
                ->values();
        }

        return response()->json($response);
    }

    public function create(array $payload, User $user)
    {
        $branchId = (int) $payload['branch_id'];

        $employee = $this->employeeOrFail($payload['employee_uuid'] ?? '', $branchId);

        $scheduleId = null;
        $patientId = null;

        if (!empty($payload['schedule_code'])) {
            $schedule = $this->scheduleOrFail($payload['schedule_code'], $branchId);

            if (!$this->notes->wasAssigned($employee->employee_id, $schedule->schedule_id)) {
                throw new Exception('This employee was not assigned to that schedule.', 422);
            }

            $scheduleId = $schedule->schedule_id;
            $patientId = $schedule->patient_id;
        } elseif (!empty($payload['patient_uuid'])) {
            $patient = $this->patientOrFail($payload['patient_uuid'], $branchId);

            if (!$this->notes->isAdmitted($patient)) {
                throw new Exception('Pick an admitted patient or a homecare schedule.', 422);
            }

            $patientId = $patient->patient_id;
        }

        $note = $this->notes->create([
            'branch_id' => $branchId,
            'employee_id' => $employee->employee_id,
            'schedule_id' => $scheduleId,
            'patient_id' => $patientId,
            'note' => $this->cleanNote($payload['note'] ?? ''),
            'recorded_by' => $user->employee?->employee_id,
        ]);

        return response()->json([
            'message' => 'Note added.',
            'data' => (new EmployeeNoteResource($this->notes->load($note)))->resolve(),
        ], 201);
    }

    public function update(array $payload, string $uuid)
    {
        $note = $this->noteOrFail($uuid, (int) $payload['branch_id']);

        $note->update(['note' => $this->cleanNote($payload['note'] ?? '')]);

        return response()->json([
            'message' => 'Note updated.',
            'data' => (new EmployeeNoteResource($this->notes->load($note)))->resolve(),
        ]);
    }

    public function delete(array $payload, string $uuid)
    {
        $this->noteOrFail($uuid, (int) $payload['branch_id'])->delete();

        return response()->json(['message' => 'Note deleted.']);
    }

    private function paginated($page)
    {
        return response()->json([
            'data' => EmployeeNoteResource::collection(collect($page->items()))->resolve(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
                'per_page' => $page->perPage(),
            ],
        ]);
    }

    private function cleanNote(string $note): string
    {
        $note = trim($note);

        if (mb_strlen($note) < self::NOTE_MIN) {
            throw new Exception('Write a note of at least ' . self::NOTE_MIN . ' characters.', 422);
        }

        return mb_substr($note, 0, 1000);
    }

    private function employeeOrFail(string $uuid, int $branchId): Employee
    {
        return $this->notes->findEmployee($uuid, $branchId)
            ?? throw new Exception('Employee not found in this branch.', 404);
    }

    private function scheduleOrFail(string $code, int $branchId): Schedule
    {
        return $this->notes->findSchedule($code, $branchId)
            ?? throw new Exception('Schedule not found.', 404);
    }

    private function patientOrFail(string $uuid, int $branchId): Patient
    {
        return $this->notes->findPatient($uuid, $branchId)
            ?? throw new Exception('Patient not found.', 404);
    }

    private function noteOrFail(string $uuid, int $branchId)
    {
        return $this->notes->findInBranch($uuid, $branchId)
            ?? throw new Exception('Note not found.', 404);
    }
}
