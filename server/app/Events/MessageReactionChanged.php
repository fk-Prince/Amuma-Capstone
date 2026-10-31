<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageReactionChanged implements ShouldBroadcastNow
{
    public function __construct(
        private int $conversationId,
        private int $messageId,
        private array $reactions,
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
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'reactions' => $this->reactions,
            'branch_uuid' => $this->branchUuid,
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageReaction';
    }
}