<?php

namespace App\Service;

use App\Models\AuditLog;
use App\Models\User;
use App\Repository\AuditLogRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditService
{
    private ?string $batchUuid = null;

    private array $roles = [];

    public function __construct(private AuditLogRepository $logs) {}

    public function model(Model $model, string $action): void
    {
        $exclude = array_flip($model->auditExclude());

        if ($action === AuditLog::ACTION_CREATED) {
            $old = [];
            $new = array_diff_key($model->getAttributes(), $exclude);
        } elseif ($action === AuditLog::ACTION_DELETED) {
            $old = array_diff_key($model->getAttributes(), $exclude);
            $new = [];
        } else {
            $new = array_diff_key($model->getChanges(), $exclude);
            $old = array_intersect_key($model->getOriginal(), $new);

            if (!$new) {
                return;
            }
        }

        $this->write(
            $action,
            $model->auditModule(),
            $model,
            old: $old ?: null,
            new: $new ?: null,
            branchId: $model->auditBranchId(),
        );
    }

    public function log(
        string $action,
        string $module,
        ?Model $subject = null,
        ?string $description = null,
        ?User $actor = null,
    ): void {
        $this->write($action, $module, $subject, description: $description, actor: $actor);
    }

    private function write(
        string $action,
        string $module,
        ?Model $subject = null,
        ?array $old = null,
        ?array $new = null,
        ?string $description = null,
        ?int $branchId = null,
        ?User $actor = null,
    ): void {
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }

        $request = request();
        $actor ??= Auth::user();
        $branchId ??= $this->requestBranchId();

        $this->logs->create([
            'user_id' => $actor?->user_id,
            'branch_id' => $branchId,
            'actor_role' => $this->roleFor($actor, $branchId),
            'action' => $action,
            'module' => $module,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'auditable_uuid' => $subject?->getAttribute('uuid'),
            'old_values' => $old,
            'new_values' => $new,
            'description' => $description,
            'batch_uuid' => $this->batchUuid ??= (string) Str::uuid(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function requestBranchId(): ?int
    {
        $branchId = request()->input('branch_id');

        return is_numeric($branchId) ? (int) $branchId : null;
    }

    private function roleFor(?User $user, ?int $branchId): ?string
    {
        if (!$user) {
            return null;
        }

        $key = $user->getKey() . ':' . $branchId;

        if (!array_key_exists($key, $this->roles)) {
            $this->roles[$key] = $this->resolveRole($user, $branchId);
        }

        return $this->roles[$key];
    }

    private function resolveRole(User $user, ?int $branchId): ?string
    {
        if ($user->employee) {
            return $branchId
                ? $user->employee->employeeBranch()->where('branch_id', $branchId)->value('role_name')
                : null;
        }

        return $user->client()->exists() ? 'client' : null;
    }
}
