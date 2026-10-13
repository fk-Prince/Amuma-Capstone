<?php

namespace App\Service;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use App\Guard\BranchGuard;
use App\Http\Resources\AuditLogDetailResource;
use App\Http\Resources\AuditLogResource;
use App\Models\User;
use App\Repository\AuditLogRepository;
use Exception;

class AuditLogService
{
    public function __construct(private AuditLogRepository $auditLogRepository) {}

    public function listAuditLogs(array $payload, User $user)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::AuditLogs, PermissionAction::Read);

        return AuditLogResource::collection(
            $this->auditLogRepository->paginateForBranch(
                $branch->branch_id,
                $payload,
                (int) ($payload['per_page'] ?? 15),
            )
        );
    }

    public function showAuditLog(string $uuid, array $payload, User $user)
    {
        $branch = BranchGuard::resolveBranch($payload['branch_uuid']);
        AuthGuard::requireModule($user, $branch->branch_id, ModuleEnum::AuditLogs, PermissionAction::Read);

        $log = $this->auditLogRepository->findForBranch($uuid, $branch->branch_id);

        if (!$log) {
            throw new Exception(__('Audit log not found.'), 404);
        }

        return new AuditLogDetailResource($log);
    }
}
