<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageSent implements ShouldBroadcastNow
{
    /**
     * @param string[] $channelNames Who should receive this message. A family
     *   thread goes to the branch inbox and the one client; a staff thread
     *   goes only to the two employees, never branch-wide.
     */
    public function __construct(
        private Message $message,
        private array $channelNames
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
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }
}
