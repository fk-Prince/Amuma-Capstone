<?php

namespace App\Interfaces;

interface IBookingPayment
{
    public function bookingBilling(array $payload);
}
