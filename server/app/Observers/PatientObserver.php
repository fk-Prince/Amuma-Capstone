<?php

namespace App\Observers;

use App\Models\Patient;

class PatientObserver
{
    /**
     * Handle the Patient "created" event.
     */
    public function created(Patient $patient): void
    {
        //
    }

    /**
     * Handle the Patient "updated" event.
     */
    public function updated(Patient $patient): void
    {
        // $patient->load('bookings');

        // foreach ($patient->bookings as $patientBooking) {
        //     $booking = $patientBooking->booking;
        //     if (!$booking) {
        //         continue;
        //     }
        //     $bookingData = $booking->booking_data ?? [];
        //     $bookingData['patient'] = array_merge(
        //         $bookingData['patient'] ?? [],
        //         [
        //             'first_name'     => $patient->first_name,
        //             'middle_name'    => $patient->middle_name,
        //             'last_name'      => $patient->last_name,
        //             'gender'         => $patient->gender,
        //             'citizenship'    => $patient->citizenship,
        //             'date_of_birth'  => optional($patient->date_of_birth)->format('Y-m-d'),
        //             'phone_number'   => $patient->phone_number,
        //             'height'         => $patient->height,
        //             'weight'         => $patient->weight,
        //             'blood_type'     => $patient->blood_type,
        //         ]
        //     );
        //     $booking->booking_data = $bookingData;
        //     $booking->saveQuietly();
        // }
    }

    /**
     * Handle the Patient "deleted" event.
     */
    public function deleted(Patient $patient): void
    {
        //
    }

    /**
     * Handle the Patient "restored" event.
     */
    public function restored(Patient $patient): void
    {
        //
    }

    /**
     * Handle the Patient "force deleted" event.
     */
    public function forceDeleted(Patient $patient): void
    {
        //
    }
}
