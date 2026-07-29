<?php

namespace App\Enums;

use Illuminate\Support\Facades\Log;

enum PaymentWebhookEnum: string
{
    case SUBSCRIPTION = 'SUBSCRIPTION';
    case BOOKING_FACILITY = 'BOOKING_FACILITY';

    public static function fromPayload(array $payload): self
    {
        return self::tryFrom(
            $payload['metadata']['payment_type']
                ?? $payload['payment_type']
                ?? ''
        ) ?? throw new \Exception('Unknown payment webhook type');
    }
}
