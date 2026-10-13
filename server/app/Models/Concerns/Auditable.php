<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Service\AuditService;
use Illuminate\Support\Str;

/**
 * @method static void created(\Closure|string $callback)
 * @method static void updated(\Closure|string $callback)
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn($model) => app(AuditService::class)->model($model, AuditLog::ACTION_CREATED));
        static::updated(fn($model) => app(AuditService::class)->model($model, AuditLog::ACTION_UPDATED));
    }

    public function auditExclude(): array
    {
        return ['password', 'remember_token', 'updated_at'];
    }

    public function auditModule(): string
    {
        return property_exists($this, 'auditModule')
            ? $this->auditModule
            : Str::snake(class_basename($this));
    }

    public function auditBranchId(): ?int
    {
        $branchId = $this->getAttribute('branch_id');

        return is_numeric($branchId) ? (int) $branchId : null;
    }
}
