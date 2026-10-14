<?php

namespace App\Service;

use App\Http\Resources\EmployeeNoteResource;
use App\Models\Employee;
use App\Models\EmployeeNote;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\User;
use App\Repository\EmployeeNoteRepository;
use Exception;

class EmployeeNoteService
{
    private const NOTE_MIN = 3;

    public function __construct(private EmployeeNoteRepository $notes) {}

    public function list(array $payload, User $user)
    {
        $branchId = (int) $payload['branch_id'];
        $viewerId = $this->authorOf($user)->employee_id;
        $perPage = (int) ($payload['per_page'] ?? 10);

        if (!empty($payload['schedule_code'])) {
            $schedule = $this->scheduleOrFail($payload['schedule_code'], $branchId);

            return response()->json([
                'data' => EmployeeNoteResource::collection(
                    $this->notes->forSchedule($branchId, $schedule->schedule_id, $viewerId)
                )->resolve(),
            ]);
        }

        if (!empty($payload['patient_uuid'])) {
            $patient = $this->patientOrFail($payload['patient_uuid'], $branchId);

            return $this->paginated(
                $this->notes->paginateForPatient($branchId, $patient->patient_id, $viewerId, $perPage)
            );
        }

        $employee = $this->employeeOrFail($payload['employee_uuid'] ?? '', $branchId);

        return $this->paginated(
            $this->notes->paginateForEmployee($branchId, $employee->employee_id, $viewerId, $perPage)
        );
    }

    public function create(array $payload, User $user)
    {
        $branchId = (int) $payload['branch_id'];

        $employee = $this->authorOf($user);

        $scheduleId = null;
        $patientId = null;

        if (!empty($payload['schedule_code'])) {
            $schedule = $this->scheduleOrFail($payload['schedule_code'], $branchId);

            if (!$this->notes->isAssigned($employee->employee_id, $schedule->schedule_id)) {
                throw new Exception('Only employees assigned to this schedule can leave a note on it.', 403);
            }

            $scheduleId = $schedule->schedule_id;
            $patientId = $schedule->patient_id;
        } elseif (!empty($payload['patient_uuid'])) {
            $patientId = $this->patientOrFail($payload['patient_uuid'], $branchId)->patient_id;
        }

        $note = $this->notes->create([
            'branch_id' => $branchId,
            'employee_id' => $employee->employee_id,
            'schedule_id' => $scheduleId,
            'patient_id' => $patientId,
            'note' => $this->cleanNote($payload['note'] ?? ''),
        ]);

        return response()->json([
            'message' => 'Note added.',
            'data' => (new EmployeeNoteResource($this->notes->load($note)))->resolve(),
        ], 201);
    }

    public function update(array $payload, string $uuid, User $user)
    {
        $note = $this->ownNoteOrFail($uuid, (int) $payload['branch_id'], $user);

        $note->update(['note' => $this->cleanNote($payload['note'] ?? '')]);

        return response()->json([
            'message' => 'Note updated.',
            'data' => (new EmployeeNoteResource($this->notes->load($note)))->resolve(),
        ]);
    }

    public function delete(array $payload, string $uuid, User $user)
    {
        $this->ownNoteOrFail($uuid, (int) $payload['branch_id'], $user)->delete();

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

    private function authorOf(User $user): Employee
    {
        return $user->employee ?? throw new Exception('Only staff can use notes.', 403);
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

    private function ownNoteOrFail(string $uuid, int $branchId, User $user): EmployeeNote
    {
        $note = $this->notes->findInBranch($uuid, $branchId)
            ?? throw new Exception('Note not found.', 404);

        if ($note->employee_id !== $this->authorOf($user)->employee_id) {
            throw new Exception('You can only change your own notes.', 403);
        }

        return $note;
    }
}
