<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Events\NotificationEvent;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Resources\EmployeeAssignedScheduleResource;
use App\Http\Resources\EmployeeScheduleResource;
use App\Repository\ScheduleRepository;
use App\Http\Resources\ScheduleResource;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\Invoice;
use App\Models\Module;
use App\Models\User;
use App\Repository\InvoiceRepository;
use App\Repository\NotificationRepository;
use App\Repository\OnlineScheduleRepository;
use App\Repository\PatientRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Schedule;
use App\Models\ScheduleService as ScheduleServiceModel;
use Exception;

class ScheduleService
{
    public function __construct(
        private ScheduleRepository $scheduleRepository,
        private PatientRepository $patientRepository,
        private InvoiceRepository $invoiceRepository,
        private NotificationRepository $notificationRepository,
        private NotificationService $notificationService,
        private OnlineScheduleRepository $onlineScheduleRepository
    ) {}

    public function createSchedule(array $payload)
    {

        return DB::transaction(function () use ($payload) {

            $patient = $this->patientRepository->findByFields([
                ['uuid', '=', $payload['patient_uuid']]
            ]);
            $scheduledAt = Carbon::createFromFormat(
                'Y-m-d H:i',
                "{$payload['date']} {$payload['preferred_time']}"
            );

            $note = $payload['note'] ?? null;

            $scheduleData = [
                'patient_id'   => $patient->patient_id,
                'scheduled_at' => $scheduledAt,
                'status'       => Schedule::STATUS_PENDING,
                'category'     => 'Facility',
                'note'         => is_string($note) && trim($note) !== ''
                    ? mb_substr(trim($note), 0, 500)
                    : null,
            ];

            $schedule = $this->scheduleRepository->create($scheduleData);
            $invoice = $this->invoiceRepository->create([
                'branch_id' => $payload['branch_id'],
                'status' => Invoice::STATUS_PENDING,
                'total_amount' => 0
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
                ]);
            }

            $invoice->update([
                'total_amount' => $total,
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

        $schedule = $this->scheduleRepository->findByFields([
            ['schedule_id', '=', $payload['schedule_id']]
        ]);

        $newStatus = strtolower($payload['status'] ?? '');

        // Completing or cancelling closes the schedule out rather than
        // committing the employee to be free at that time going forward, so
        // there is nothing to re-check for a conflict against.
        if (in_array($newStatus, [Schedule::STATUS_COMPLETED, Schedule::STATUS_CANCELLED], true)) {
            return $this->updateSchedule($user, $schedule, $payload);
        }

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

        $serviceNames = $schedule->scheduleServices
            ->mapWithKeys(fn($ss) => [$ss->schedule_services_id => $ss->service?->service_name ?? 'ADL']);

        $conflicts = [];

        foreach ($payload['assignments'] ?? [] as $assignment) {
            $employeeId = (int) $assignment['employee_id'];

            if ($busyEmployeeScheduleCodes->has($employeeId)) {
                $conflictScheduleCodes = $busyEmployeeScheduleCodes[$employeeId] ?? [];

                $conflicts[] = [
                    'employee_id' => $employeeId,
                    'employee_name' => $employeeNames[$employeeId] ?? "Employee #{$employeeId}",
                    'schedule_services_id' => $assignment['schedule_services_id'] ?? null,
                    'service_name' => $serviceNames[$assignment['schedule_services_id'] ?? null] ?? 'ADL',
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

        return $this->updateSchedule($user, $schedule, $payload);
    }


    private const ASSISTANT_NOTE = 'Assistant';

    private function assistingIds(?string $type, $employeeIds, $branchId)
    {
        $employeeIds = collect($employeeIds);

        if ($type !== 'Medical' || $employeeIds->isEmpty()) {
            return collect();
        }

        return EmployeeBranch::whereIn('employee_id', $employeeIds->all())
            ->where('branch_id', $branchId)
            ->where('role_name', 'caregiver')
            ->pluck('employee_id')
            ->map(fn($id) => (int) $id);
    }

    private function assertAssignmentType(Schedule $schedule, $employeeIds, $branchId): void
    {
        $employeeIds = collect($employeeIds);

        if ($employeeIds->isEmpty()) {
            return;
        }

        $isFacility = $schedule->category === Schedule::CATEGORYFACILITY;

        $allowed = $isFacility
            ? ['facility', 'inhouse facility', 'both', 'homecare + inhouse facility']
            : ['online', 'homecare', 'both', 'homecare + inhouse facility'];

        $types = EmployeeBranch::whereIn('employee_id', $employeeIds->all())
            ->where('branch_id', $branchId)
            ->pluck('assignment_type', 'employee_id');

        foreach ($employeeIds as $employeeId) {
            $type = $types[$employeeId] ?? null;

            if ($type !== null && !in_array(strtolower($type), $allowed, true)) {
                throw new Exception(
                    $isFacility
                        ? 'Only facility staff can be assigned to a facility schedule.'
                        : 'Only homecare staff can be assigned to a homecare schedule.',
                    422
                );
            }
        }
    }

    private function assertStaffing(?string $type, $employeeIds, $branchId): void
    {
        $employeeIds = collect($employeeIds);

        if ($employeeIds->isEmpty()) {
            return;
        }

        $allowed = match ($type) {
            'Medical' => ['nurse', 'caregiver'],
            'ADL' => ['caregiver'],
            default => null,
        };

        if ($allowed === null) {
            return;
        }

        $roles = EmployeeBranch::whereIn('employee_id', $employeeIds->all())
            ->where('branch_id', $branchId)
            ->pluck('role_name', 'employee_id');

        foreach ($employeeIds as $employeeId) {
            if (!in_array($roles[$employeeId] ?? null, $allowed, true)) {
                throw new Exception(
                    $type === 'Medical'
                        ? 'Only a nurse, or a caregiver assisting a nurse, can be assigned to a Medical service.'
                        : 'Only a caregiver can be assigned to an ADL service.',
                    422
                );
            }
        }

        if ($type === 'Medical' && !$employeeIds->contains(fn($id) => ($roles[$id] ?? null) === 'nurse')) {
            throw new Exception(
                'A medical service needs a nurse. A caregiver can only assist, not be assigned alone.',
                422
            );
        }
    }

    private function conflictMessage(int $employeeId, array $codes): string
    {
        $name = Employee::find($employeeId)?->full_name ?? "Employee #{$employeeId}";

        return "{$name} has a schedule conflict with " . implode(', ', $codes) . '.';
    }

    public function updateSchedule(User $user, Schedule $schedule, array $payload)
    {
        return DB::transaction(function () use ($user, $schedule, $payload) {
            if (strtolower($schedule->status) === Schedule::STATUS_CANCELLED) {
                throw new Exception(
                    'This schedule has been cancelled and can no longer be updated.',
                    422
                );
            }

            if (strtolower($schedule->status) === Schedule::STATUS_COMPLETED) {
                throw new Exception(
                    'This schedule has been completed and can no longer be updated.',
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

            $isFinalizing = in_array(
                $newStatus,
                [Schedule::STATUS_COMPLETED, Schedule::STATUS_CANCELLED],
                true
            );

            $note = $payload['note'] ?? null;

            $note = is_string($note) && trim($note) !== ''
                ? mb_substr(trim($note), 0, 500)
                : null;

            $schedule->update([
                'status' => $newStatus,
                'scheduled_at' => $targetStart,
                'note' => $note,
            ]);

            $schedule->load('scheduleServices.service');

            $targetDurationMinutes = $this->scheduleRepository->calculateScheduleDurationMinutes($schedule);
            $targetEnd = $targetStart->copy()->addMinutes($targetDurationMinutes);

            $assignmentsByService = collect($payload['assignments'])
                ->groupBy('schedule_services_id');

            $assignedEmployeeIds = [];
            $previouslyAssignedIds = [];

            foreach ($assignmentsByService as $scheduleServicesId => $rows) {
                $scheduleService = $schedule->scheduleServices()
                    ->where('schedule_services_id', $scheduleServicesId)
                    ->firstOrFail();

                $rowEmployeeIds = collect($rows)->pluck('employee_id')->filter()->map(fn($id) => (int) $id)->unique()->values();

                if (!$isFinalizing) {
                    $this->assertStaffing($scheduleService->type, $rowEmployeeIds, $branch->branch_id);

                    $activeIds = $scheduleService->assigned()
                        ->where('is_active', true)
                        ->pluck('employee_id')
                        ->map(fn($id) => (int) $id);

                    $this->assertAssignmentType(
                        $schedule,
                        $rowEmployeeIds->diff($activeIds),
                        $branch->branch_id
                    );
                }

                $assisting = $this->assistingIds($scheduleService->type, $rowEmployeeIds, $branch->branch_id);

                foreach ($scheduleService->assigned()->get() as $existing) {
                    if ($existing->is_active) {
                        $previouslyAssignedIds[] = (int) $existing->employee_id;
                    }

                    $existing->update(['is_active' => false]);
                }

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

                    $isFinalizing = in_array(
                        $newStatus,
                        [Schedule::STATUS_COMPLETED, Schedule::STATUS_CANCELLED],
                        true
                    );

                    [$startTime, $endTime] = $this->assignmentTimes($assignment, $scheduleService->type);
                    [$windowStart, $windowEnd] = $this->assignmentWindow($targetStart, $targetEnd, $startTime, $endTime);

                    $conflictCodes = $isFinalizing ? [] : $this->scheduleRepository->activeConflictCodes(
                        $employeeId,
                        (string) $branch->branch_id,
                        $schedule->schedule_id,
                        $windowStart,
                        $windowEnd
                    );

                    if ($conflictCodes) {
                        throw new Exception($this->conflictMessage($employeeId, $conflictCodes), 409);
                    }

                    $roleName = EmployeeBranch::where('employee_id', $employeeId)
                        ->where('branch_id', $branch->branch_id)
                        ->value('role_name');

                    $note = $assignment['note'] ?? null;

                    $note = is_string($note) && trim($note) !== ''
                        ? mb_substr(trim($note), 0, 255)
                        : null;

                    if ($assisting->contains($employeeId)) {
                        $note = self::ASSISTANT_NOTE;
                    }

                    $scheduleService->assigned()->updateOrCreate(
                        ['employee_id' => $employeeId],
                        [
                            'is_active' => true,
                            'note' => $note,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                        ]
                    );

                    $assignedEmployeeIds[] = $employeeId;
                }
            }

            if ($isFinalizing) {
                $this->onlineScheduleRepository->forceClockOutSchedule($schedule->schedule_id);
            }

            if ($newStatus === Schedule::STATUS_CANCELLED) {
                $this->cancelledSchedule($user, $schedule);
            }

            $this->notifyAssignedStaff(
                $user,
                $schedule,
                $branch,
                array_unique($assignedEmployeeIds),
                $isFinalizing ? $assignedEmployeeIds : $previouslyAssignedIds,
                $targetStart
            );

            $when = $targetStart->format('M j, Y \a\t g:i A');

            $this->notifyFamily($user, $schedule, match (true) {
                $newStatus === Schedule::STATUS_CANCELLED => "was cancelled",
                $newStatus === Schedule::STATUS_COMPLETED => "was completed",
                !$isDateTimeUnchanged => "was moved to {$when}",
                default => "on {$when} was updated",
            });

            return response()->json([
                'message' => 'Schedule updated successfully.',
                'data' => new ScheduleResource($schedule->fresh([
                    'scheduleServices.assigned.onlineSchedules',
                    'scheduleServices.service',
                    'patient',
                    'location',
                    'patient.location',
                ])),
            ]);
        });
    }

    private function notifyAssignedStaff(
        User $user,
        Schedule $schedule,
        object $branch,
        array $employeeIds,
        array $alreadyAssignedIds,
        Carbon $scheduledAt
    ): void {
        if (empty($employeeIds)) {
            return;
        }

        $schedule->loadMissing('patient');

        $patientName = $schedule->patient?->display_name ?? '';

        $details = " {$schedule->schedule_code}"
            . ($patientName !== '' ? " for {$patientName}" : '')
            . ' on ' . $scheduledAt->format('M j, Y \a\t g:i A');

        $employees = Employee::with('users')
            ->whereIn('employee_id', $employeeIds)
            ->get();

        foreach ($employees as $employee) {
            if (!$employee->user_id || !$employee->users?->uuid) {
                continue;
            }

            $message = in_array((int) $employee->employee_id, $alreadyAssignedIds, true)
                ? "Schedule{$details} has been updated."
                : "You have been assigned to schedule{$details}.";

            $this->notificationRepository->create([
                'branch_id' => $branch->branch_id,
                'to_user_id' => $employee->user_id,
                'from_user_id' => $user->user_id,
                'message_type' => 'Schedule',
                'message' => $message,
            ]);

            event(new NotificationEvent(
                $employee->users->uuid,
                (string) $branch->uuid,
                $message,
                (string) $schedule->schedule_id,
                'Schedule',
                null,
            ));
        }
    }

    private function notifyFamily(?User $user, Schedule $schedule, string $change): void
    {
        $schedule->loadMissing('patient', 'scheduleServices');

        $patient = $schedule->patient;

        if (!$patient) {
            return;
        }

        $kind = $schedule->scheduleServices->contains('type', ScheduleServiceModel::TYPE_ADL)
            ? 'daily care (ADL)'
            : 'medical';

        $this->notificationService->notifyPatientAccess(
            $patient,
            "{$patient->display_name}'s {$kind} schedule {$schedule->schedule_code} {$change}.",
            'Schedule',
            $user
        );
    }

    private function cancelledSchedule(User $user, Schedule $schedule)
    {
        $schedule->load('scheduleServices.invoiceServices.invoice.allocations.refundAllocations');

        $invoiceIds = $schedule->scheduleServices
            ->flatMap(fn($scheduleService) => $scheduleService->invoiceServices)
            ->pluck('invoice_id')
            ->filter()
            ->unique();

        if ($invoiceIds->isEmpty()) {
            return;
        }

        $invoices = Invoice::with('allocations.refundAllocations.refund.transaction')
            ->whereIn('invoice_id', $invoiceIds)
            ->where('status', '!=', Invoice::STATUS_VOID)
            ->get();

        foreach ($invoices as $invoice) {
            // $this->refundService->createRefundFull(
            //     $invoice,
            //     'Invoice refunded due to schedule cancellation.'
            // );

            // $invoice->update([
            //     'status' => Invoice::STATUS_VOID,
            // ]);
            $this->notifyCashier($user, $schedule, $invoice);
        }
    }

    private function notifyCashier(User $user, Schedule $schedule, Invoice $invoice): void
    {
        $module = Module::where('module_name', ModuleEnum::BillingAndInvoices->value)
            ->first();

        if (!$module) {
            return;
        }

        $recipients = Employee::query()
            ->with('users')
            ->whereHas(
                'employeeBranch',
                fn($q) => $q->where('branch_id', $invoice->branch_id)
            )
            ->whereHas(
                'permissions',
                fn($q) => $q->where('module_id', $module->module_id)
                    ->where('branch_id', $invoice->branch_id)
                    ->where('can_read', true)
            )
            ->get();

        $message = "Schedule {$schedule->schedule_code} was cancelled."
            . " Please void invoice {$invoice->invoice_code}.";

        foreach ($recipients as $employee) {
            if (!$employee->user_id || !$employee->users?->uuid) {
                continue;
            }

            $this->notificationRepository->create([
                'branch_id' => $invoice->branch_id,
                'to_user_id' => $employee->user_id,
                'from_user_id' => $user->user_id,
                'message_type' => 'Billing',
                'message' => $message,
            ]);

            event(new NotificationEvent(
                $employee->users->uuid,
                (string) $invoice->branch?->uuid,
                $message,
                (string) $invoice->invoice_id,
                'Billing',
                null,
            ));
        }
    }

    public function overview(array $payload)
    {
        return $this->scheduleRepository->getOverview($payload);
    }

    public function deductInvoice(array $payload)
    {
        $schedule = $this->scheduleRepository->findByFields([
            ['schedule_id', '=', $payload['schedule_id']],
        ]);

        if (!$schedule) {
            throw new Exception('Schedule dont exists', 404);
        }

        $amount = round((float) ($payload['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw new Exception('Enter a deduction amount greater than zero.', 422);
        }

        $reason = trim((string) ($payload['reason'] ?? ''));

        return DB::transaction(function () use ($payload, $schedule, $amount, $reason) {
            $serviceIds = !empty($payload['schedule_services_id'])
                ? [$payload['schedule_services_id']]
                : $schedule->scheduleServices()->pluck('schedule_services_id')->all();

            $invoiceId = InvoiceServices::whereIn('schedule_services_id', $serviceIds)->value('invoice_id');

            $invoice = $invoiceId
                ? Invoice::where('invoice_id', $invoiceId)
                    ->where('branch_id', $payload['branch_id'])
                    ->lockForUpdate()
                    ->first()
                : null;

            if (!$invoice) {
                throw new Exception('No invoice found for this schedule.', 404);
            }

            if (in_array($invoice->status, Invoice::CLOSED_STATUSES, true)) {
                throw new Exception('This invoice is void or written off and can no longer be adjusted.', 422);
            }

            if ($amount > (float) $invoice->adjusted_total + 0.01) {
                throw new Exception("The deduction can't exceed the invoice's billed total.", 422);
            }

            InvoiceAdjustment::create([
                'invoice_id' => $invoice->invoice_id,
                'type' => InvoiceAdjustment::TYPE_CORRECTION,
                'amount' => -$amount,
                'reason' => "Late/gap deduction for schedule {$schedule->schedule_code}"
                    . ($reason !== '' ? ": {$reason}" : '.'),
            ]);

            $invoice->syncStatus();

            return response()->json([
                'message' => 'The deduction has been applied to the invoice.',
                'invoice_code' => $invoice->invoice_code,
            ]);
        });
    }

    public function retrieveSchedule(User $user, array $payload)
    {
        if (!empty($payload['assigned_only'])) {
            $payload['employee_id'] = $user->employee?->employee_id;
        }

        return ScheduleResource::collection($this->scheduleRepository->retrievePaginate($payload));
    }

    public function employeeSchedules(User $user, array $payload)
    {
        if (!empty($payload['assigned_only'])) {
            $payload['employee_id'] = $user->employee?->employee_id;
        }

        $employees = $this->scheduleRepository->getEmployeesWithSchedules($payload)
            ->sortBy(fn($employeeBranch) => strtolower($employeeBranch->employees?->full_name ?? ''))
            ->values();

        return EmployeeAssignedScheduleResource::collection($employees);
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

    private function assignmentTimes(array $row, ?string $serviceType): array
    {
        if ($serviceType !== ScheduleServiceModel::TYPE_ADL) {
            return [null, null];
        }

        $start = trim((string) ($row['start_time'] ?? ''));
        $end = trim((string) ($row['end_time'] ?? ''));

        if ($start === '' && $end === '') {
            return [null, null];
        }

        if ($start === '' || $end === '') {
            throw new Exception('Pick both a start time and an end time.', 422);
        }

        try {
            $startAt = Carbon::createFromFormat('H:i', substr($start, 0, 5));
            $endAt = Carbon::createFromFormat('H:i', substr($end, 0, 5));
        } catch (\Throwable $e) {
            throw new Exception('Start and end times must look like 08:00.', 422);
        }

        if ($endAt->lte($startAt)) {
            throw new Exception('The end time must be later than the start time.', 422);
        }

        return [$startAt->format('H:i:s'), $endAt->format('H:i:s')];
    }

    private function assignmentWindow(Carbon $scheduleStart, Carbon $scheduleEnd, ?string $startTime, ?string $endTime): array
    {
        if (!$startTime || !$endTime) {
            return [$scheduleStart, $scheduleEnd];
        }

        return [
            $scheduleStart->copy()->setTimeFromTimeString($startTime),
            $scheduleStart->copy()->setTimeFromTimeString($endTime),
        ];
    }

    public function assignEmployee(array $payload)
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

            if (strtolower($schedule->status) === Schedule::STATUS_COMPLETED) {
                throw new Exception(
                    'This schedule has been completed and can no longer be updated.',
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

            $careTeamChanged = false;

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

                $this->assertStaffing($scheduleService->type, $desiredEmployeeIds, $branchId);

                $this->assertAssignmentType(
                    $schedule,
                    $desiredEmployeeIds->diff($currentlyActiveIds),
                    $branchId
                );

                $assisting = $this->assistingIds($scheduleService->type, $desiredEmployeeIds, $branchId);

                $timesByEmployee = $assignments
                    ->filter(fn($row) => !empty($row['employee_id']))
                    ->mapWithKeys(fn($row) => [
                        (int) $row['employee_id'] => $this->assignmentTimes($row, $scheduleService->type),
                    ]);

                foreach ($desiredEmployeeIds as $employeeId) {
                    if ($currentlyActiveIds->contains($employeeId)) {
                        continue;
                    }

                    [$windowStart, $windowEnd] = $this->assignmentWindow(
                        $targetStart,
                        $targetEnd,
                        ...($timesByEmployee->get($employeeId) ?? [null, null])
                    );

                    $conflictCodes = $this->scheduleRepository->activeConflictCodes(
                        $employeeId,
                        (string) $branchId,
                        $schedule->schedule_id,
                        $windowStart,
                        $windowEnd
                    );

                    if ($conflictCodes) {
                        throw new Exception($this->conflictMessage($employeeId, $conflictCodes), 409);
                    }
                }

                foreach ($currentlyActive as $currentAssigned) {
                    if ($desiredEmployeeIds->contains((int) $currentAssigned->employee_id)) {
                        continue;
                    }

                    $currentAssigned->update(['is_active' => false]);
                    $careTeamChanged = true;
                }


                foreach ($desiredEmployeeIds as $employeeId) {
                    $hasNote = $notesByEmployee->has($employeeId);
                    $note = $notesByEmployee->get($employeeId);

                    if ($assisting->contains($employeeId)) {
                        $hasNote = true;
                        $note = self::ASSISTANT_NOTE;
                    }

                    [$startTime, $endTime] = $timesByEmployee->get($employeeId) ?? [null, null];

                    if ($currentlyActiveIds->contains($employeeId)) {
                        $current = $currentlyActive->firstWhere('employee_id', $employeeId);

                        if ($hasNote) {
                            $current?->update(['note' => $note]);
                        }

                        if ($timesByEmployee->has($employeeId)) {
                            $current?->update([
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                            ]);
                        }

                        continue;
                    }

                    $careTeamChanged = true;

                    $existingRow = $scheduleService->assigned()
                        ->where('employee_id', $employeeId)
                        ->first();

                    if ($existingRow) {
                        $existingRow->update([
                            ...array_filter([
                                'is_active' => true,
                                'note' => $note,
                            ], fn($value, $key) => $key !== 'note' || $hasNote, ARRAY_FILTER_USE_BOTH),
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                        ]);
                    } else {
                        $scheduleService->assigned()->create([
                            'employee_id' => $employeeId,
                            'is_active' => true,
                            'note' => $note,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                        ]);
                    }
                }
            }

            if ($careTeamChanged) {
                $this->notifyFamily(
                    $payload['user'] ?? request()->user(),
                    $schedule,
                    'on ' . $targetStart->format('M j, Y \a\t g:i A') . ' has an updated care team'
                );
            }

            return response()->json([
                'message' => 'Schedule services have been updated successfully.',
                'data' => new ScheduleResource($schedule->fresh([
                    'scheduleServices.assigned.onlineSchedules',
                    'scheduleServices.service',
                    'patient',
                    'location',
                    'patient.location',
                ])),
            ]);
        });
    }
}
