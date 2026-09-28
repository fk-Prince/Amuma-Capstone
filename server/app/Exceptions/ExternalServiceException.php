<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ExternalServiceException extends Exception
{
    public const PAYMENT_FAILED = 'Payment failed.';
    public const THIRD_PARTY_ERROR = 'Third-party service error.';

    public static function payment(?Throwable $previous = null): self
    {
        return new self(self::PAYMENT_FAILED, 502, $previous);
    }

    public static function thirdParty(?Throwable $previous = null): self
    {
        return new self(self::THIRD_PARTY_ERROR, 502, $previous);
    }
}
