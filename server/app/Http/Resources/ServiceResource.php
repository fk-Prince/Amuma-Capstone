<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'service_id' => $this->service_id,
            'service_uuid' => $this->service_uuid,
            'service_name' => $this->service_name,
            'category_id' => $this->category_id,
            'category_name' => $this->categories?->category_name,
            'price' => $this->price,
            'maximum_duration' => $this->maximum_duration,
            'is_available' => $this->is_available,
            'type' => $this->type,
            'type_formatted' => match ($this->type) {
                'online' => 'Homecare Services',
                'facility' => 'In-house Facility',
                'both' => 'Online and Inhouse',
            },
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
