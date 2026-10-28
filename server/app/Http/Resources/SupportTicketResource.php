<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'ticket_code' => $this->ticket_code,
            'source' => $this->isPublic() ? 'public' : 'branch',
            'subject' => $this->subject,
            'category' => $this->category,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'last_reply_at' => $this->last_reply_at
                ?? ($this->relationLoaded('replies') ? $this->replies->max('created_at') : null),
            'submitted_by' => [
                'name' => $this->submitter_name,
                'email' => $this->submitter_email,
            ],
            'branch' => [
                'uuid' => $this->branch?->uuid,
                'name' => $this->branch?->name,
            ],
            'agency' => [
                'name' => $this->branch?->agencies?->name,
            ],
            'replies' => $this->whenLoaded('replies', fn() => SupportTicketReplyResource::collection(
                $this->replies->each(fn($reply) => $reply->setRelation('ticket', $this->resource))
            )),
        ];
    }
}
