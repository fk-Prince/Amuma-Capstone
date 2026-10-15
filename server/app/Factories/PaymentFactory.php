<?php

namespace App\Factories;

use App\Interfaces\IPaymentMethod;
use App\Service\Payment\CardPayment;
use App\Service\Payment\GCashPayment;
use Illuminate\Support\Facades\Log;

class PaymentFactory
{
    public static function make(string $method): IPaymentMethod
    {
        return match ($method) {
            'GCASH' => app(GCashPayment::class),
            'CREDIT-CARD' => app(CardPayment::class),
            default => throw new \Exception("Unsupported payment method"),
        };
    }
}
