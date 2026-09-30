<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\Branch;
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

        $branch = $this->branch_uuid !== ''
            ? Branch::where('uuid', $this->branch_uuid)->first(['uuid', 'name', 'image'])
            : null;

        return [
            'message' => $this->message,
            'user_uuid' => $this->user_uuid,
            'reference_id' => $this->reference_id,
            'branch_uuid' => $this->branch_uuid,
            'branch' => $branch ? [
                'uuid' => $branch->uuid,
                'name' => $branch->name,
                'image' => $branch->image,
            ] : null,
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
