<?php

namespace App\Repository;

use App\Models\BranchContract;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Models\Schedule;
use Carbon\Carbon;

class BranchContractRepository
{

    public function create(array $payload)
    {
        return BranchContract::create($payload);
    }

    public function findByField(array $conditions)
    {
        return BranchContract::where($conditions)->first();
    }

    public function findAllByConditions(array $conditions)
    {
        return BranchContract::where($conditions)->get();
    }

    public function all(string $branchId)
    {
        return BranchContract::where('branch_id', $branchId)->get();
    }

    public function overview(array $payload, string $branchId)
    {
        return BranchContract::where('branch_id', $branchId)
            ->where('is_active', true)
            ->count();
    }


    public function dashboardStats(string $branchId): array
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $prevMonthStart = $monthStart->copy()->subMonth();

        $weekStart = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek();

        $branchPatients = fn($query) => $query->where('branch_id', $branchId);

        // ---- Homecare -------------------------------------------------
        $activeHomecarePatients = Schedule::query()
            ->whereHas('patient', $branchPatients)
            ->where('category', 'Homecare')
            ->whereIn('status', [Schedule::STATUS_PENDING, Schedule::STATUS_ONGOING])
            ->distinct('patient_id')
            ->count('patient_id');

        $caregivers = Employee::query()
            ->where('status', Employee::STATUS_ACTIVE)
            ->whereHas('employeeBranch', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                    ->where('role_name', 'caregiver');
            })
            ->count();

        $scheduledVisits = Schedule::query()
            ->whereHas('patient', $branchPatients)
            ->where('category', 'Homecare')
            ->whereBetween('scheduled_at', [$weekStart, $weekEnd])
            ->whereNot('status', Schedule::STATUS_CANCELLED)
            ->count();

        // ---- Facility -------------------------------------------------
        $activePlans = BranchContract::where('branch_id', $branchId)
            ->where('is_active', true)
            ->count();


        $patientsWithPlan = PatientAdmission::query()
            ->whereHas('patient', $branchPatients)
            ->whereIn('status', [
                PatientAdmission::STATUS_ADMITTED,
                PatientAdmission::STATUS_WAITING,
            ])
            ->whereHas('invoiceAdmission', fn($query) => $query->whereNotNull('branch_contract_id'))
            ->distinct('patient_id')
            ->count('patient_id');

        $newPatientsThisMonth = Patient::where('branch_id', $branchId)
            ->where('created_at', '>=', $monthStart)
            ->count();

        return [
            'total_active_plans' => $activePlans,
            'patient_with_plan' => $patientsWithPlan,
            'new_monthy_patients' => $newPatientsThisMonth,
            'patient_retention' => $this->retentionRate(
                $branchId,
                'Facility',
                $prevMonthStart,
                $monthStart,
                $now
            ),

            'active_patient' => $activeHomecarePatients,
            'caregivers' => $caregivers,
            'scheduled_visits' => $scheduledVisits,
            'homecare_retention' => $this->retentionRate(
                $branchId,
                'Homecare',
                $prevMonthStart,
                $monthStart,
                $now
            ),
        ];
    }


    private function retentionRate(
        string $branchId,
        string $category,
        Carbon $prevMonthStart,
        Carbon $monthStart,
        Carbon $now
    ): string {
        $patientsBetween = function (Carbon $from, Carbon $to) use ($branchId, $category) {
            return Schedule::query()
                ->whereHas('patient', fn($query) => $query->where('branch_id', $branchId))
                ->where('category', $category)
                ->whereNot('status', Schedule::STATUS_CANCELLED)
                ->whereBetween('scheduled_at', [$from, $to])
                ->distinct()
                ->pluck('patient_id');
        };

        $previous = $patientsBetween($prevMonthStart, $monthStart);

        if ($previous->isEmpty()) {
            return '—';
        }

        $current = $patientsBetween($monthStart, $now);
        $retained = $previous->intersect($current)->count();

        return round(($retained / $previous->count()) * 100) . '%';
    }
}
