<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->notification_id,
            'message' => $this->message,
            'message_type' => $this->message_type,

            // The client works in terms of "unread"; the column stores the
            // inverse, so the flip happens here rather than in every consumer.
            'unread' => ! $this->has_read,

            'created_at' => $this->created_at,

            'branch' => $this->whenLoaded('branch', fn() => [
                'uuid' => $this->branch?->uuid,
                'name' => $this->branch?->name,
            ]),
        ];
    }
}
