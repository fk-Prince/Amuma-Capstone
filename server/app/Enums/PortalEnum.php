<?php

namespace App\Enums;

use App\Models\User;

enum PortalEnum: string
{
    case Staff = 'staff';
    case Client = 'client';
    case Checkout = 'checkout';

    public function allows(User $user): bool
    {
        return match ($this) {
            self::Staff => $user->isEmployee,
            self::Client => $user->isClient || $user->isSystemOwner,
            self::Checkout => true,
        };
    }

    public function mismatchMessage(): string
    {
        return match ($this) {
            self::Staff => __('This account isn\'t a staff account. Please use the Client Portal to sign in.'),
            self::Client => __('This account isn\'t a client account. Please use the Staff Portal to sign in.'),
            self::Checkout => __('This account can\'t be used here.'),
        };
    }
}
