<?php

namespace App\Observers;

use App\Models\Client;

class ClientObserver
{
    /**
     * Handle the Client "created" event.
     */
    public function created(Client $client): void
    {
        $client->load('user.bookings');

        foreach ($client->bookings as $booking) {

            $data = $booking->booking_data ?? [];

            $data['guardian'] = array_merge(
                $data['guardian'] ?? [],
                [
                    'first_name'   => $client->first_name,
                    'last_name'    => $client->last_name,
                    'phone_number' => $client->phone_number,
                    'email'        => $client->user?->email,
                    'address'      => $client->location?->address ?? null,
                ]
            );

            $booking->updateQuietly([
                'booking_data' => $data,
            ]);
        }
    }

    /**
     * Handle the Client "updated" event.
     */
    public function updated(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "deleted" event.
     */
    public function deleted(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "restored" event.
     */
    public function restored(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "force deleted" event.
     */
    public function forceDeleted(Client $client): void
    {
        //
    }
}
