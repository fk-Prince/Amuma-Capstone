<?php

namespace App\Repository;

use App\Models\AuditLog;
use App\Models\Employee;

class AuditLogRepository
{
    public function create(array $payload): AuditLog
    {
        return AuditLog::create($payload);
    }

    public function paginateForBranch(int $branchId, array $filters, int $perPage)
    {
        return $this->branchQuery($branchId)
            ->select([
                'audit_log_id',
                'uuid',
                'user_id',
                'actor_role',
                'action',
                'module',
                'auditable_type',
                'description',
                'created_at',
            ])
            ->with($this->actorRelations($branchId))
            ->when($filters['module'] ?? null, fn($query, $module) => $query->where('module', $module))
            ->when($filters['action'] ?? null, fn($query, $action) => $query->where('action', $action))
            ->when($filters['date_from'] ?? null, fn($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $term = '%' . trim($search) . '%';

                $query->where(function ($inner) use ($term) {
                    $inner->where('description', 'ilike', $term)
                        ->orWhereHas('user', function ($user) use ($term) {
                            $user->where('email', 'ilike', $term)
                                ->orWhereHas('employee', fn($employee) => $employee->whereRaw("concat(first_name, ' ', last_name) ilike ?", [$term]))
                                ->orWhereHas('client', fn($client) => $client->whereRaw("concat(first_name, ' ', last_name) ilike ?", [$term]));
                        });
                });
            })
            ->orderByDesc('audit_log_id')
            ->paginate($perPage);
    }

    public function findForBranch(string $uuid, int $branchId): ?AuditLog
    {
        return $this->branchQuery($branchId)
            ->where('uuid', $uuid)
            ->with($this->actorRelations($branchId))
            ->first();
    }

    private function actorRelations(int $branchId): array
    {
        return [
            'user:user_id,email',
            'user.employee:employee_id,user_id,first_name,last_name',
            'user.employee.employeeBranch' => fn($assignment) => $assignment
                ->select(['employee_id', 'branch_id', 'role_name'])
                ->where('branch_id', $branchId),
            'user.client:client_id,user_id,first_name,last_name',
        ];
    }

    private function branchQuery(int $branchId)
    {
        return AuditLog::query()->where(function ($query) use ($branchId) {
            $query->where('branch_id', $branchId)
                ->orWhere(function ($auth) use ($branchId) {
                    $auth->where('module', 'auth')
                        ->whereIn(
                            'user_id',
                            Employee::query()
                                ->whereHas('employeeBranch', fn($assignment) => $assignment->where('branch_id', $branchId))
                                ->select('user_id')
                        );
                });
        });
    }
}
