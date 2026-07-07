<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Log;

class NotificationEvent implements ShouldBroadcastNow
{
    private string $message;
    private string $user_uuid;
    private string  $reference_id;
    private  string $branch_uuid;

    public function __construct(string $user_uuid, string $message, string $reference_id, string $branch_uuid)
    {
        $this->user_uuid = $user_uuid;
        $this->message = $message;
        $this->reference_id = $reference_id;
        $this->branch_uuid = $branch_uuid;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('Notification.' . $this->user_uuid);
    }
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'user_uuid' => $this->user_uuid,
            'reference_id' => $this->reference_id,
            'branch_uuid' => $this->branch_uuid
        ];
    }
    public function broadcastAs(): string
    {
        return 'NotificationEvent';
    }
}
