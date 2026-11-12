<?php

namespace App\Repository;

use App\Models\BookingStaffRequest;
use App\Models\Employee;
use App\Models\EmployeeBranch;
use App\Models\PatientAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingStaffRequestRepository
{
    private const HOMECARE_ASSIGNMENTS = ['online', 'homecare', 'both', 'homecare + inhouse facility'];

    public function options(int $branchId, array $roles, ?int $serviceId, ?int $patientId, ?string $search, int $perPage, int $page)
    {
        $term = $search ? '%' . $search . '%' : null;

        $query = $this->homecareStaff($branchId, $roles)
            ->select('employee_branches.*')
            ->where('status', EmployeeBranch::STATUS_ACTIVE)
            ->when($term, fn($q) => $q->whereHas('employees', fn($employee) => $employee
                ->where('first_name', 'ilike', $term)
                ->orWhere('last_name', 'ilike', $term)
                ->orWhereRaw("concat_ws(' ', first_name, last_name) ilike ?", [$term])))
            ->with([
                'employees',
                'employees.users:user_id,uuid',
            ]);

        if ($patientId) {
            $query->selectRaw(
                'exists (
                    select 1 from schedule_assigned sa
                    join schedule_services ss on ss.schedule_services_id = sa.schedule_services_id
                    join schedules s on s.schedule_id = ss.schedule_id
                    where s.patient_id = ? and sa.employee_id = employee_branches.employee_id
                ) as cared_before',
                [$patientId]
            )->orderByDesc('cared_before');
        }

        if ($serviceId) {
            $query->withExists([
                'employeeServices as is_specialized' => fn($q) => $q
                    ->where('service_id', $serviceId)
                    ->where('is_active', true),
            ])->orderByDesc('is_specialized');
        }

        return $query
            ->orderByRaw(
                "(select case when jsonb_typeof(e.achievements::jsonb) = 'array'
                        then jsonb_array_length(e.achievements::jsonb) else 0 end
                  from employees e where e.employee_id = employee_branches.employee_id) desc nulls last"
            )
            ->orderBy(
                Employee::select('first_name')
                    ->whereColumn('employees.employee_id', 'employee_branches.employee_id')
            )
            ->orderBy('employee_branches.employee_branch_id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function eligibleEmployeeIds(int $branchId, array $roles, ?int $serviceId, array $userUuids): Collection
    {
        return $this->homecareStaff($branchId, $roles)
            ->where('status', EmployeeBranch::STATUS_ACTIVE)
            ->when($serviceId, fn($q) => $q->qualifiedFor([$serviceId]))
            ->whereHas('employees.users', fn($q) => $q->whereIn('uuid', $userUuids))
            ->pluck('employee_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();
    }

    public function hasPatientAccess(int $clientId, int $patientId): bool
    {
        return PatientAccess::where('client_id', $clientId)
            ->where('patient_id', $patientId)
            ->where('have_access', true)
            ->exists();
    }

    public function attach(int $bookingId, array $employeeIds): void
    {
        foreach (array_unique($employeeIds) as $employeeId) {
            BookingStaffRequest::create([
                'booking_id' => $bookingId,
                'employee_id' => $employeeId,
            ]);
        }
    }

    private function homecareStaff(int $branchId, array $roles): Builder
    {
        return EmployeeBranch::query()
            ->where('branch_id', $branchId)
            ->whereIn('role_name', $roles)
            ->whereIn(DB::raw('lower(assignment_type)'), self::HOMECARE_ASSIGNMENTS);
    }
}
