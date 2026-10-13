<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AuditLogDetailResource extends AuditLogResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
        ];
    }
}
