<?php

namespace App\Factories;

use App\Enums\PaymentWebhookEnum;
use App\Hooks\FacilityWebhook;
use App\Hooks\SubscriptionWebhook;

class PaymentWebhook
{
    public static function makePayment(array $payload)
    {

        return match (PaymentWebhookEnum::fromPayload($payload)) {

            PaymentWebhookEnum::SUBSCRIPTION =>
            app(SubscriptionWebhook::class),

            PaymentWebhookEnum::BOOKING_FACILITY =>
            app(FacilityWebhook::class),
        };
    }
}
