<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Resources\EmployeeScheduleResource;
use App\Repository\ScheduleRepository;
use App\Http\Resources\ScheduleResource;
use App\Models\User;
use App\Repository\BranchRepository;
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
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user,  $branch->branch_id,   ModuleEnum::Schedules,  PermissionAction::Create);

        return DB::transaction(function () use ($payload, $branch) {

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
                'status'       => 'Pending',
                'category'     => 'Facility',
            ];

            $schedule = $this->scheduleRepository->create($scheduleData);
            $invoice = $this->invoiceRepository->create([
                'branch_id' => $branch->branch_id,
                'status' => 'pending',
                'is_collected' => false,
            ]);

            $total = 0;
            foreach ($payload['services'] as $service) {
                $total += $service['price'];
                $scheduleService = $schedule->scheduleServices()->create([
                    'service_id' => $service['service_id'],
                    'type' => 'Medical',
                    'status' => 'pending'
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

            return new ScheduleResource($schedule->fresh([
                'scheduleServices.service',
            ]));
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

        $employeeNames = collect($employees)
            ->mapWithKeys(fn(EmployeeScheduleResource $e) => [(int) $e->employee_id => $e->full_name]);

        $busyIds = collect($employees)
            ->filter(fn(EmployeeScheduleResource $e) => $e->is_busy)
            ->map(fn(EmployeeScheduleResource $e) => (int) $e->employee_id)
            ->all();

        $schedule = $this->scheduleRepository->findByUuid([
            ['schedule_id', '=', $payload['schedule_id']]
        ]);

        $serviceNames = $schedule->scheduleServices
            ->mapWithKeys(fn($ss) => [$ss->schedule_services_id => $ss->service->service_name ?? 'Unknown Service']);
        $conflicts = [];

        foreach ($payload['assignments'] ?? [] as $assignment) {
            $employeeId = (int) $assignment['employee_id'];
            if (in_array($employeeId, $busyIds, true)) {
                $employeeName = $employeeNames[$employeeId] ?? "Employee #{$employeeId}";
                $serviceName = $serviceNames[$assignment['schedule_services_id']] ?? 'this service';

                $conflicts[] = "{$employeeName} conflicts with {$serviceName}";
            }
        }

        if (!empty($conflicts)) {
            return response()->json([
                'message' => implode('; ', $conflicts),
            ], 422);
        }
        return $this->updateSchedule($schedule, $payload);
    }

    public function updateSchedule(Schedule $schedule, array $payload)
    {
        $schedule->update([
            'status' => strtolower($payload['status']),
            'scheduled_at' => Carbon::parse("{$payload['date']} {$payload['preferred_time']}"),
        ]);

        foreach ($payload['assignments'] as $assignment) {
            $scheduleService = $schedule->scheduleServices()
                ->where('schedule_services_id', $assignment['schedule_services_id'])
                ->firstOrFail();

            $scheduleService->assigned()->updateOrCreate(
                ['employee_id' => $assignment['employee_id']],
                []
            );
        }

        return response()->json([
            'message' => 'Schedule updated successfully.',
            'data' => new ScheduleResource($schedule->fresh(['scheduleServices.assigned', 'scheduleServices.service'])),
        ]);
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

    public function assignEmployee(User $user, array $payload)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);

        AuthGuard::requireModule($user,  $branch->branch_id,  ModuleEnum::Schedules,   PermissionAction::Create);

        $schedule = $this->scheduleRepository->findByUuid([
            ['schedule_id', '=', $payload['schedule_id']]
        ]);

        if (!$schedule) {
            throw new Exception('Schedule dont exists', 404);
        }

        foreach ($payload['assignments'] as $assignment) {
            $scheduleService = $schedule->scheduleServices()
                ->where('schedule_services_id', $assignment['schedule_services_id'])
                ->firstOrFail();

            $scheduleService->assigned()->updateOrCreate(
                ['employee_id' => $assignment['employee_id']],
                []
            );
            // $scheduleService->assigned()->delete();
            // $scheduleService->assigned()->create([
            //     'employee_id' => $assignment['employee_id'],
            // ]);
        }

        return response()->json([
            'message' => 'Schedule services have been assigned to employees successfully.',
            'data' => $this->retrieveSchedule($user, $payload)
        ]);
    }
}
