<?php

namespace App\Service;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use App\Repository\AuditLogRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditService
{
    public const ADDRESS_FIELDS = ['street', 'city', 'province', 'country', 'full_address', 'longitude', 'latitude'];

    private ?string $batchUuid = null;

    private array $roles = [];

    private array $branchIds = [];

    public function __construct(private AuditLogRepository $logs) {}

    public function model(Model $model, string $action): void
    {
        $exclude = $model->auditExclude();

        if ($action === AuditLog::ACTION_CREATED) {
            $old = [];
            $new = $this->values($model, array_diff(array_keys($model->getAttributes()), $exclude));
        } else {
            $keys = array_diff(array_keys($model->getChanges()), $exclude);

            if (!$keys) {
                return;
            }

            $new = $this->values($model, $keys);
            $old = $this->values($model, $keys, true);
            [$old, $new] = $this->flatten($old, $new);
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
        ?array $old = null,
        ?array $new = null,
    ): void {
        $this->write($action, $module, $subject, $old, $new, $description, actor: $actor);
    }

    public function addressChange(Model $owner, string $module, array $old, array $new, ?int $branchId = null): void
    {
        $oldChanges = [];
        $newChanges = [];

        foreach (self::ADDRESS_FIELDS as $field) {
            if (!array_key_exists($field, $new)) {
                continue;
            }

            $before = $old[$field] ?? null;
            $after = $new[$field] ?? null;

            $same = is_numeric($before) && is_numeric($after)
                ? abs((float) $before - (float) $after) < 0.0000001
                : (string) $before === (string) $after;

            if ($same) {
                continue;
            }

            if ($before !== null && $before !== '') {
                $oldChanges["address.{$field}"] = $before;
            }

            if ($after !== null && $after !== '') {
                $newChanges["address.{$field}"] = $after;
            }
        }

        if (!$oldChanges && !$newChanges) {
            return;
        }

        $this->write(
            AuditLog::ACTION_UPDATED,
            $module,
            $owner,
            $oldChanges ?: null,
            $newChanges ?: null,
            'Changed the address.',
            $branchId,
        );
    }

    private function values(Model $model, array $keys, bool $original = false): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $original ? $model->getOriginal($key) : $model->getAttribute($key);
        }

        return $values;
    }

    private function flatten(array $old, array $new): array
    {
        foreach (array_keys($new) as $key) {
            if (
                is_array($old[$key] ?? null)
                && is_array($new[$key])
                && !array_is_list($old[$key])
                && !array_is_list($new[$key])
            ) {
                $diff = $this->arrayDiff($old[$key], $new[$key], $key);
                unset($old[$key], $new[$key]);
                $old += $diff['old'];
                $new += $diff['new'];
            }
        }

        return [$old, $new];
    }

    private function arrayDiff(array $old, array $new, string $prefix): array
    {
        $changedOld = [];
        $changedNew = [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $key) {
            $path = "{$prefix}.{$key}";
            $before = $old[$key] ?? null;
            $after = $new[$key] ?? null;

            if (
                is_array($before)
                && is_array($after)
                && !array_is_list($before)
                && !array_is_list($after)
            ) {
                $nested = $this->arrayDiff($before, $after, $path);
                $changedOld += $nested['old'];
                $changedNew += $nested['new'];
            } elseif ($before !== $after) {
                if (array_key_exists($key, $old)) {
                    $changedOld[$path] = $before;
                }

                if (array_key_exists($key, $new)) {
                    $changedNew[$path] = $after;
                }
            }
        }

        return ['old' => $changedOld, 'new' => $changedNew];
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
            'auditable_uuid' => $this->subjectUuid($subject),
            'old_values' => $old,
            'new_values' => $new,
            'description' => $description,
            'batch_uuid' => $this->batchUuid ??= (string) Str::uuid(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function subjectUuid(?Model $subject): ?string
    {
        if (!$subject) {
            return null;
        }

        $column = collect(method_exists($subject, 'uniqueIds') ? $subject->uniqueIds() : [])
            ->first(fn($name) => str_contains($name, 'uuid')) ?? 'uuid';

        $value = $subject->getAttribute($column);

        return is_string($value) && Str::isUuid($value) ? $value : null;
    }

    public function requestBranchId(): ?int
    {
        $request = request();
        $branchId = $request->input('branch_id');

        if (is_numeric($branchId)) {
            return (int) $branchId;
        }

        $branchUuid = $request->input('branch_uuid');

        if (!is_string($branchUuid) || !Str::isUuid($branchUuid)) {
            return null;
        }

        if (!array_key_exists($branchUuid, $this->branchIds)) {
            $this->branchIds[$branchUuid] = Branch::where('uuid', $branchUuid)->value('branch_id');
        }

        return $this->branchIds[$branchUuid];
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
