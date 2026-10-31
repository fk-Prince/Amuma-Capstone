<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;


class MessageStatusUpdated implements ShouldBroadcastNow
{
    public function __construct(
        private int $conversationId,
        private string $status, // 'delivered' | 'read'
        private int $upToMessageId,
        private string $at,
        private int $byUserId,
        private string $readerSide, // 'staff' | 'client'
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
            'status' => $this->status,
            'up_to_message_id' => $this->upToMessageId,
            'at' => $this->at,
            'by_user_id' => $this->byUserId,
            'reader_side' => $this->readerSide,
            'branch_uuid' => $this->branchUuid,
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageStatus';
    }
}