<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Log;

class NotificationEvent implements ShouldBroadcastNow
{

    public function __construct(
        private string $user_uuid,
        private string $branch_uuid,
        private string $message,
        private string $reference_id,
        private string $message_type,
        private mixed $booking
    ) {}


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
            'branch_uuid' => $this->branch_uuid,
            'message_type' => $this->message_type,
            'booking' => [
                ...($this->booking?->toArray() ?? []),
                'status' => $this->booking?->status ?? 'Pending',
            ],
        ];
    }
    public function broadcastAs(): string
    {
        return 'NotificationEvent';
    }
}
