<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageSent implements ShouldBroadcastNow
{
    public function __construct(
        private Message $message,
        private array $channelNames,
        private ?string $branchUuid = null
    ) {}

    public function broadcastOn(): array
    {
        return array_map(
            fn(string $name) => new PrivateChannel($name),
            $this->channelNames
        );
    }

    public function broadcastWith(): array
    {
        return [
            ...$this->message->toChat(),
            'conversation_id' => $this->message->conversation_id,
            'branch_uuid' => $this->branchUuid,
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }
}
