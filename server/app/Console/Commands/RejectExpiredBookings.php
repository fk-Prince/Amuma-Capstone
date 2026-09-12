<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Service\BookingService;
use Illuminate\Console\Command;
use Throwable;

class RejectExpiredBookings extends Command
{
    protected $signature = 'bookings:reject-expired';

    protected $description = 'Expire bookings still pending after valid_until, returning anything already paid';

    public function __construct(
        private BookingService $bookings
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $expired = 0;
        $refunded = 0;

        Booking::where('status', Booking::STATUS_PENDING)
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', now())
            ->chunkById(200, function ($bookings) use (&$expired, &$refunded) {
                foreach ($bookings as $booking) {
                    try {
                        $refunded += $this->bookings->expire($booking) ? 1 : 0;
                        $expired++;
                    } catch (Throwable $e) {
                        $this->error(
                            "Booking {$booking->booking_id} could not be expired: {$e->getMessage()}"
                        );
                    }
                }
            });

        $this->info("Expired {$expired} booking(s), refunded {$refunded}.");

        return self::SUCCESS;
    }
}
