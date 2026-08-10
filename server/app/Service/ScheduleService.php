<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Resources\EmployeeScheduleResource;
use App\Repository\ScheduleRepository;
use App\Http\Resources\ScheduleResource;
use App\Models\Invoice;
use App\Models\User;
use App\Repository\InvoiceRepository;
use App\Repository\PatientRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Schedule;
use Exception;
use Illuminate\Support\Facades\Log;

class ScheduleService
{
    public function __construct(
        private ScheduleRepository $scheduleRepository,
        private PatientRepository $patientRepository,
        private InvoiceRepository $invoiceRepository
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
                'is_collected' => false,
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

        if (!empty($conflicts) && empty($payload['confirm_conflicts'])) {
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
            $schedule->update([
                'status' => strtolower($payload['status']),
                'scheduled_at' => Carbon::parse("{$payload['date']} {$payload['preferred_time']}"),
            ]);

            foreach ($payload['assignments'] as $assignment) {
                $scheduleService = $schedule->scheduleServices()
                    ->where('schedule_services_id', $assignment['schedule_services_id'])
                    ->firstOrFail();

                $scheduleService->assigned()->delete();

                if (!empty($assignment['employee_id'])) {
                    $scheduleService->assigned()->create([
                        'employee_id' => $assignment['employee_id'],
                    ]);
                }
            }

            return response()->json([
                'message' => 'Schedule updated successfully.',
                'data' => new ScheduleResource($schedule->fresh(['scheduleServices.assigned', 'scheduleServices.service', 'patient'])),
            ]);
        });
    }

    public function retrieveSchedule(User $user, array $payload)
    {
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

    // public function assignEmployee(User $user, array $payload)
    // {
    //     return DB::transaction(function () use ($user, $payload) {
    //         $schedule = $this->scheduleRepository->findByFields([
    //             ['schedule_id', '=', $payload['schedule_id']]
    //         ]);

    //         if (!$schedule) {
    //             throw new Exception('Schedule dont exists', 404);
    //         }

    //         foreach ($payload['assignments'] ?? [] as $assignment) {
    //             $scheduleService = $schedule->scheduleServices()
    //                 ->where('schedule_services_id', $assignment['schedule_service_id'])
    //                 ->first();

    //             if (!$scheduleService) {
    //                 throw new Exception("Schedule service {$assignment['schedule_service_id']} not found for this schedule.", 404);
    //             }

    //             $existingAssigned = $scheduleService->assigned()
    //                 ->with('online')
    //                 ->first();

    //             $newEmployeeId = !empty($assignment['employee_id'])
    //                 ? (int) $assignment['employee_id']
    //                 : null;

    //             if (!$existingAssigned) {
    //                 if ($newEmployeeId) {
    //                     $scheduleService->assigned()->create([
    //                         'employee_id' => $newEmployeeId,
    //                     ]);
    //                 }
    //                 continue;
    //             }

    //             $hasScanHistory = $existingAssigned->online->isNotEmpty();
    //             $employeeUnchanged = $existingAssigned->employee_id === $newEmployeeId;

    //             if ($employeeUnchanged) {
    //                 continue;
    //             }

    //             if ($hasScanHistory) {
    //                 throw new Exception(
    //                     "Cannot reassign {$scheduleService->schedule_services_id}: employee already has check-in/out records for this service.",
    //                     409
    //                 );
    //             }

    //             if ($newEmployeeId === null) {
    //                 $existingAssigned->delete();
    //             } else {
    //                 $existingAssigned->update([
    //                     'employee_id' => $newEmployeeId,
    //                 ]);
    //             }
    //         }

    //         return response()->json([
    //             'message' => 'Schedule services have been assigned to employees successfully.',
    //             'data' => $this->retrieveSchedule($user, $payload)
    //         ]);
    //     });
    // }

    public function assignEmployee(User $user, array $payload)
    {
        return DB::transaction(function () use ($user, $payload) {
            $schedule = $this->scheduleRepository->findByFields([
                ['schedule_id', '=', $payload['schedule_id']]
            ]);

            if (!$schedule) {
                throw new Exception('Schedule dont exists', 404);
            }


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

                $currentlyActive = $scheduleService->assigned()
                    ->where('is_active', true)
                    ->get();

                // Deactivate/delete anyone active who's no longer in the desired set
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

                $currentlyActiveIds = $currentlyActive
                    ->pluck('employee_id')
                    ->map(fn($id) => (int) $id);

                // Activate/create anyone in the desired set who isn't already active
                foreach ($desiredEmployeeIds as $employeeId) {
                    if ($currentlyActiveIds->contains($employeeId)) {
                        continue;
                    }

                    $existingRow = $scheduleService->assigned()
                        ->where('employee_id', $employeeId)
                        ->first();

                    if ($existingRow) {
                        $existingRow->update(['is_active' => true]);
                    } else {
                        $scheduleService->assigned()->create([
                            'employee_id' => $employeeId,
                            'is_active' => true,
                        ]);
                    }
                }
            }

            return response()->json([
                'message' => 'Schedule services have been updated successfully.',
                'data' => $this->retrieveSchedule($user, $payload)
            ]);
        });
    }
}
