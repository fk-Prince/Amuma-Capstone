<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketReplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $author = $this->user?->employee ?? $this->user?->client ?? $this->user?->systemOwner;
        return [
            'id' => $this->support_ticket_reply_id,
            'message' => $this->message,
            'attachment' => $this->attachment,
            'is_support' => (bool) $this->user?->isSystemOwner,
            'author_name' => trim(($author?->first_name ?? '') . ' ' . ($author?->last_name ?? '')) ?: null,
            'created_at' => $this->created_at,
        ];
    }
}
