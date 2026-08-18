<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use Carbon\Carbon;

class RejectExpiredBookings extends Command
{
    protected $signature = 'bookings:reject-expired';

    protected $description = 'Reject bookings that are still pending after valid_until';

    public function handle(): int
    {
        Booking::where('status', Booking::STATUS_PENDING)
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', now())
            ->chunkById(200, function ($bookings) use (&$count) {
                foreach ($bookings as $booking) {
                    $booking->update([
                        'status' => Booking::STATUS_EXPIRED,
                    ]);
                }
            });
        return self::SUCCESS;
    }
}
