<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Log;

class SupportTicketUpdated implements ShouldBroadcastNow
{
    public function __construct(
        public string $ticketUuid,
        public string $status,
        public ?array $reply = null,
    ) {}

    public static function safe(self $event): void
    {
        try {
            event($event);
        } catch (\Throwable $e) {
            Log::error('Support ticket broadcast failed', ['error' => $e->getMessage()]);
        }
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("SupportTicket.{$this->ticketUuid}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'support.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'uuid' => $this->ticketUuid,
            'status' => $this->status,
            'reply' => $this->reply,
        ];
    }
}
