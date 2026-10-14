<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->user?->employee ?? $this->user?->client;

        return [
            'uuid' => $this->uuid,
            'actor_name' => $profile
                ? trim("{$profile->first_name} {$profile->last_name}")
                : null,
            'actor_email' => $this->user?->email,
            'actor_role' => $this->actorRole(),
            'action' => $this->action,
            'module' => $this->module,
            'record' => $this->auditable_type ? class_basename($this->auditable_type) : null,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }

    private function actorRole(): ?string
    {
        if ($this->actor_role) {
            return $this->actor_role;
        }

        $employee = $this->user?->employee;

        if ($employee?->relationLoaded('employeeBranch')) {
            return $employee->employeeBranch->first()?->role_name;
        }

        return $this->user?->client ? 'client' : null;
    }
}
