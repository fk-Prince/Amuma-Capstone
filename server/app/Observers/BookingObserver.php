<?php

namespace App\Observers;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking): void
    {
        $bookingData = $booking->booking_data;

        $service = is_array($bookingData) ? ($bookingData['service'] ?? null) : ($bookingData->service ?? null);


        $type = is_array($service) ? ($service['type'] ?? null) : ($service->type ?? null);

        if (! in_array($type, [Booking::TYPE_MEDICAL, Booking::TYPE_ADL], true)) {
            return;
        }


        $date = is_array($service) ? ($service['date'] ?? null) : ($service->date ?? null);
        $preferedTime = is_array($service) ? ($service['prefered_time'] ?? null) : ($service->prefered_time ?? null);

        if (! $date) {
            return;
        }


        $dateTime = $preferedTime ? "{$date} {$preferedTime}" : $date;

        try {
            $validUntil = Carbon::parse($dateTime);
        } catch (\Throwable $e) {
            Log::error('Failed to parse valid_until', ['error' => $e->getMessage(), 'dateTime' => $dateTime]);
            return;
        }
        $booking->valid_until = $validUntil;
        $booking->saveQuietly();
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "deleted" event.
     */
    public function deleted(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "restored" event.
     */
    public function restored(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "force deleted" event.
     */
    public function forceDeleted(Booking $booking): void
    {
        //
    }
}
