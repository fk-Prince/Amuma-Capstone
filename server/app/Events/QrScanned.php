<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QrScanned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $token,
        public string $type,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("qr.{$this->token}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'qr.scanned';
    }

    public function broadcastWith(): array
    {
        return [
            'token' => $this->token,
            'type' => $this->type,
        ];
    }
}
