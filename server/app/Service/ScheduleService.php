<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Resources\EmployeeScheduleResource;
use App\Repository\ScheduleRepository;
use App\Http\Resources\ScheduleResource;
use App\Models\EmployeeBranch;
use App\Models\Invoice;
use App\Models\User;
use App\Repository\InvoiceRepository;
use App\Repository\PatientRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Schedule;
use Exception;

class ScheduleService
{
    public function __construct(
        private ScheduleRepository $scheduleRepository,
        private PatientRepository $patientRepository,
        private InvoiceRepository $invoiceRepository,
        private RefundService $refundService
    ) {}

    public function createSchedule(User $user, array $payload)
    {

        return DB::transaction(function () use ($payload) {

            $patient = $this->patientRepository->findByFields([
                ['uuid', '=', $payload['patient_uuid']]
            ]);
            $scheduledAt = Carbon::createFromFormat(
                'Y-m-d H:i',
                "{$payload['date']} {$payload['preferred_time']}"
            );

            $scheduleData = [
                'patient_id'   => $patient->patient_id,
                'scheduled_at' => $scheduledAt,
                'status'       => Schedule::STATUS_PENDING,
                'category'     => 'Facility',
            ];

            $schedule = $this->scheduleRepository->create($scheduleData);
            $invoice = $this->invoiceRepository->create([
                'branch_id' => $payload['branch_id'],
                'status' => Invoice::STATUS_PENDING,
                'total' => 0
            ]);

            $total = 0;
            foreach ($payload['services'] as $service) {
                $total += $service['price'];
                $scheduleService = $schedule->scheduleServices()->create([
                    'service_id' => $service['service_id'],
                    'type' => 'Medical',
                ]);

                $scheduleService->invoiceServices()->create([
                    'invoice_id' => $invoice->invoice_id,
                    'price' => $service['price'],
                    'note' => $payload['note'],
                ]);
            }

            $invoice->update([
                'total' => $total,
            ]);

            return response()->json([
                'message' => 'Schedule services have been created successfully.',
                'data' => new ScheduleResource($schedule->fresh([
                    'scheduleServices.service',
                    'patient',
                    'location',
                    'patient.location',
                ]))
            ]);
        });
    }

    public function checkConflictSchedule(User $user, array $payload)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::Schedules, PermissionAction::Update);

        $result = $this->scheduleRepository->getEmployeesForReassignment(
            $payload['schedule_id'],
            $branch->branch_id,
            $payload['date'],
            $payload['preferred_time'],
        );

        $employees = EmployeeScheduleResource::collection($result);

        $employeeNames = collect($result)
            ->mapWithKeys(fn($e) => [(int) $e->employee_id => $e->full_name]);

        $busyEmployeeScheduleCodes = collect($result)
            ->filter(fn($e) => $e->is_busy)
            ->mapWithKeys(function ($employee) {
                $scheduleCodes = collect($employee->employeeBranch)
                    ->flatMap(fn($branch) => $branch->scheduleAssignments ?? collect())
                    ->map(fn($assignment) => $assignment->scheduleService?->schedule)
                    ->filter(fn($schedule) => $schedule && in_array($schedule->status, ['ongoing', 'pending'], true))
                    ->map(fn($schedule) => $schedule->schedule_code)
                    ->unique()
                    ->values()
                    ->all();

                return [(int) $employee->employee_id => $scheduleCodes];
            });

        $schedule = $this->scheduleRepository->findByFields([
            ['schedule_id', '=', $payload['schedule_id']]
        ]);

        $serviceNames = $schedule->scheduleServices
            ->mapWithKeys(fn($ss) => [$ss->schedule_services_id => $ss->service->service_name ?? 'Unknown Service']);

        $conflicts = [];

        foreach ($payload['assignments'] ?? [] as $assignment) {
            $employeeId = (int) $assignment['employee_id'];

            if ($busyEmployeeScheduleCodes->has($employeeId)) {
                $conflictScheduleCodes = $busyEmployeeScheduleCodes[$employeeId] ?? [];

                $conflicts[] = [
                    'employee_id' => $employeeId,
                    'employee_name' => $employeeNames[$employeeId] ?? "Employee #{$employeeId}",
                    'schedule_services_id' => $assignment['schedule_services_id'] ?? null,
                    'service_name' => $serviceNames[$assignment['schedule_services_id'] ?? null] ?? "ADL Homecare",
                    'conflict_schedule_codes' => $conflictScheduleCodes,
                ];
            }
        }

        if (!empty($conflicts)) {
            return response()->json([
                'has_conflicts' => true,
                'conflicts' => $conflicts,
            ], 200);
        }

        return $this->updateSchedule($schedule, $payload);
    }


    public function updateSchedule(Schedule $schedule, array $payload)
    {
        return DB::transaction(function () use ($schedule, $payload) {
            if (strtolower($schedule->status) === Schedule::STATUS_CANCELLED) {
                throw new Exception(
                    'This schedule has been cancelled and can no longer be updated.',
                    422
                );
            }

            $targetStart = Carbon::parse("{$payload['date']} {$payload['preferred_time']}");

            $currentStart = $schedule->scheduled_at ? Carbon::parse($schedule->scheduled_at) : null;
            $isDateTimeUnchanged = $currentStart
                && $targetStart->format('Y-m-d H:i') === $currentStart->format('Y-m-d H:i');

            if ($targetStart->isPast() && !$isDateTimeUnchanged) {
                throw new Exception(
                    'A schedule cannot be updated to a date/time in the past.',
                    422
                );
            }

            $branch = BranchGuard::resolveBranch($payload['branch_uuid']);

            $newStatus = strtolower($payload['status']);

            $schedule->update([
                'status' => $newStatus,
                'scheduled_at' => $targetStart,
            ]);

            $schedule->load('scheduleServices.service');

            $targetDurationMinutes = $this->scheduleRepository->calculateScheduleDurationMinutes($schedule);
            $targetEnd = $targetStart->copy()->addMinutes($targetDurationMinutes);

            $assignmentsByService = collect($payload['assignments'])
                ->groupBy('schedule_services_id');

            foreach ($assignmentsByService as $scheduleServicesId => $rows) {
                $scheduleService = $schedule->scheduleServices()
                    ->where('schedule_services_id', $scheduleServicesId)
                    ->firstOrFail();

                foreach ($scheduleService->assigned()->get() as $existing) {
                    if ($existing->onlineSchedules()->exists()) {
                        $existing->update(['is_active' => false]);
                    } else {
                        $existing->delete();
                    }
                }

                $requiredRole = match ($scheduleService->type) {
                    'Medical' => 'nurse',
                    'ADL' => 'caregiver',
                    default => null,
                };

                $seen = [];

                foreach ($rows as $assignment) {
                    if (empty($assignment['employee_id'])) {
                        continue;
                    }

                    $employeeId = (int) $assignment['employee_id'];

                    if (in_array($employeeId, $seen, true)) {
                        continue;
                    }

                    $seen[] = $employeeId;

                    if ($this->scheduleRepository->employeeHasActiveConflict(
                        $employeeId,
                        $schedule->schedule_id,
                        $targetStart,
                        $targetEnd
                    )) {
                        throw new Exception(
                            'This employee is already assigned to another schedule during this time and cannot be assigned here.',
                            409
                        );
                    }

                    $roleName = EmployeeBranch::where('employee_id', $employeeId)
                        ->where('branch_id', $branch->branch_id)
                        ->value('role_name');

                    if ($requiredRole !== null && $roleName !== $requiredRole) {
                        throw new Exception(
                            "Only a {$requiredRole} can be assigned to a {$scheduleService->type} service.",
                            422
                        );
                    }

                    $note = $assignment['note'] ?? null;

                    $note = is_string($note) && trim($note) !== ''
                        ? mb_substr(trim($note), 0, 255)
                        : null;

                    $scheduleService->assigned()->updateOrCreate(
                        ['employee_id' => $employeeId],
                        ['is_active' => true, 'note' => $note]
                    );
                }
            }

            if ($newStatus === Schedule::STATUS_CANCELLED) {
                $this->refundCancelledSchedule($schedule);
            }

            return response()->json([
                'message' => 'Schedule updated successfully.',
                'data' => new ScheduleResource($schedule->fresh([
                    'scheduleServices.assigned',
                    'scheduleServices.service',
                    'patient',
                    'location',
                    'patient.location',
                ])),
            ]);
        });
    }

    private function refundCancelledSchedule(Schedule $schedule): void
    {
        $schedule->load('scheduleServices.invoiceServices.invoice.payments.refunds');

        $invoiceIds = $schedule->scheduleServices
            ->flatMap(fn($scheduleService) => $scheduleService->invoiceServices)
            ->pluck('invoice_id')
            ->filter()
            ->unique();

        if ($invoiceIds->isEmpty()) {
            return;
        }

        $invoices = Invoice::with('payments.refunds')
            ->whereIn('invoice_id', $invoiceIds)
            ->where('status', '!=', Invoice::STATUS_VOID)
            ->get();

        foreach ($invoices as $invoice) {
            $this->refundService->createRefundFull(
                $invoice,
                'Invoice refunded due to schedule cancellation.'
            );

            $invoice->update([
                'status' => Invoice::STATUS_VOID,
            ]);
        }
    }

    public function overview(array $payload)
    {
        return $this->scheduleRepository->getOverview($payload);
    }
    public function retrieveSchedule(User $user, array $payload)
    {
        if (!empty($payload['assigned_only'])) {
            $payload['employee_id'] = $user->employee?->employee_id;
        }

        return ScheduleResource::collection($this->scheduleRepository->retrievePaginate($payload));
    }

    public function availableEmployee(array $payload)
    {
        $serviceIds = $payload['service_ids'] ?? [];
        $date       = $payload['date'] ?? null;
        $time       = $payload['time'] ?? null;
        $timeSpanHours = $payload['time_span_hours'] ?? null;

        return EmployeeScheduleResource::collection(
            $this->scheduleRepository->getEmployeeAvailable($serviceIds, $payload['branch_id'], $date, $time, $timeSpanHours)
        );
    }

    public function assignEmployee(User $user, array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $schedule = $this->scheduleRepository->findByFields([
                ['schedule_id', '=', $payload['schedule_id']]
            ]);

            if (!$schedule) {
                throw new Exception('Schedule dont exists', 404);
            }

            if (strtolower($schedule->status) === Schedule::STATUS_CANCELLED) {
                throw new Exception(
                    'This schedule has been cancelled and can no longer be updated.',
                    422
                );
            }

            if ($schedule->scheduled_at && Carbon::parse($schedule->scheduled_at)->isPast()) {
                throw new Exception(
                    'This schedule has already passed and can no longer be updated.',
                    422
                );
            }

            $schedule->load('scheduleServices.service');

            $targetStart = Carbon::parse($schedule->scheduled_at);
            $targetDurationMinutes = $this->scheduleRepository->calculateScheduleDurationMinutes($schedule);
            $targetEnd = $targetStart->copy()->addMinutes($targetDurationMinutes);

            $branchId = $payload['branch_id'];

            $assignmentsByService = collect($payload['assignments'] ?? [])
                ->groupBy('schedule_services_id');

            foreach ($assignmentsByService as $scheduleServicesId => $assignments) {
                $scheduleService = $schedule->scheduleServices()
                    ->where('schedule_services_id', $scheduleServicesId)
                    ->first();

                if (!$scheduleService) {
                    throw new Exception("Schedule service {$scheduleServicesId} not found for this schedule.", 404);
                }

                $desiredEmployeeIds = $assignments
                    ->pluck('employee_id')
                    ->filter()
                    ->map(fn($id) => (int) $id)
                    ->unique()
                    ->values();

                $notesByEmployee = $assignments
                    ->filter(fn($row) => !empty($row['employee_id'])
                        && array_key_exists('note', $row))
                    ->mapWithKeys(function ($row) {
                        $note = $row['note'];

                        $note = is_string($note) && trim($note) !== ''
                            ? mb_substr(trim($note), 0, 255)
                            : null;

                        return [(int) $row['employee_id'] => $note];
                    });

                $currentlyActive = $scheduleService->assigned()
                    ->where('is_active', true)
                    ->get();

                $currentlyActiveIds = $currentlyActive
                    ->pluck('employee_id')
                    ->map(fn($id) => (int) $id);

                $requiredRole = match ($scheduleService->type) {
                    'Medical' => 'nurse',
                    'ADL' => 'caregiver',
                    default => null,
                };


                foreach ($desiredEmployeeIds as $employeeId) {
                    if ($currentlyActiveIds->contains($employeeId)) {
                        continue;
                    }

                    if ($this->scheduleRepository->employeeHasActiveConflict(
                        $employeeId,
                        $schedule->schedule_id,
                        $targetStart,
                        $targetEnd
                    )) {
                        throw new Exception(
                            'This employee is already assigned to another schedule during this time and cannot be assigned here.',
                            409
                        );
                    }

                    if ($requiredRole !== null) {
                        $roleName = EmployeeBranch::where('employee_id', $employeeId)
                            ->where('branch_id', $branchId)
                            ->value('role_name');

                        if ($roleName !== $requiredRole) {
                            throw new Exception(
                                "Only a {$requiredRole} can be assigned to a {$scheduleService->type} service.",
                                422
                            );
                        }
                    }
                }

                foreach ($currentlyActive as $currentAssigned) {
                    if ($desiredEmployeeIds->contains((int) $currentAssigned->employee_id)) {
                        continue;
                    }

                    $hasOnlineLog = $currentAssigned->onlineSchedules()->exists();

                    if ($hasOnlineLog) {
                        $currentAssigned->update(['is_active' => false]);
                    } else {
                        $currentAssigned->delete();
                    }
                }


                foreach ($desiredEmployeeIds as $employeeId) {
                    $hasNote = $notesByEmployee->has($employeeId);
                    $note = $notesByEmployee->get($employeeId);

                    if ($currentlyActiveIds->contains($employeeId)) {

                        if ($hasNote) {
                            $currentlyActive
                                ->firstWhere('employee_id', $employeeId)
                                ?->update(['note' => $note]);
                        }

                        continue;
                    }

                    $existingRow = $scheduleService->assigned()
                        ->where('employee_id', $employeeId)
                        ->first();

                    if ($existingRow) {
                        $existingRow->update(array_filter([
                            'is_active' => true,
                            'note' => $note,
                        ], fn($value, $key) => $key !== 'note' || $hasNote, ARRAY_FILTER_USE_BOTH));
                    } else {
                        $scheduleService->assigned()->create([
                            'employee_id' => $employeeId,
                            'is_active' => true,
                            'note' => $note,
                        ]);
                    }
                }
            }

            return response()->json([
                'message' => 'Schedule services have been updated successfully.',
                'data' => new ScheduleResource($schedule->fresh([
                    'scheduleServices.assigned',
                    'scheduleServices.service',
                    'patient',
                    'location',
                    'patient.location',
                ])),
            ]);
        });
    }
}
