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
        $count = 0;

        Booking::whereIn('status', [Booking::STATUS_AWAITING, Booking::STATUS_PENDING])
            ->where('valid_until', '<', Carbon::now())
            ->chunkById(200, function ($bookings) use (&$count) {
                foreach ($bookings as $booking) {
                    $booking->update(['status' => 'expired']);
                    $count++;
                }
            });

        $this->info("Expired {$count} expired bookings.");

        return self::SUCCESS;
    }
}
