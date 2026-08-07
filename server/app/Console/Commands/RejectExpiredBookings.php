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

        Booking::whereIn('status', [
            Booking::STATUS_PENDING,
        ])
            ->whereHas('service', function ($query) {
                $query->where('type', 'Medical');
            })
            ->with('service')
            ->chunkById(200, function ($bookings) use (&$count) {
                foreach ($bookings as $booking) {
                    $service = $booking->service;

                    $scheduledAt = Carbon::parse(
                        "{$service->date} {$service->preferred_time}"
                    );

                    if (now()->greaterThan($scheduledAt)) {
                        $booking->update([
                            'status' => Booking::STATUS_EXPIRED,
                        ]);

                        $count++;
                    }
                }
            });

        $this->info("Marked {$count} bookings as missed.");

        return self::SUCCESS;
    }
}
