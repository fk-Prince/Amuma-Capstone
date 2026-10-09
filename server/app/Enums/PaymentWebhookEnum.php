<?php

namespace App\Enums;


enum PaymentWebhookEnum: string
{
    case SUBSCRIPTION = 'SUBSCRIPTION';
    case RENEWAL = 'RENEWAL';
    case BOOKING_FACILITY = 'BOOKING_FACILITY';
    case PORTAL_BALANCE = 'PORTAL_BALANCE';
    public static function fromPayload(array $payload): self
    {
        return self::tryFrom(
            $payload['metadata']['payment_type']
                ?? $payload['payment_type']
                ?? ''
        ) ?? throw new \Exception('Unknown payment webhook type');
    }
}
