<?php

namespace App\Service\Utils;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;

class AuthGuard
{

    public static function requireUser(User $user)
    {
        if (!$user) {
            throw new UnauthorizedException('User not authenticated', 403);
        }

        return $user;
    }

    public static function requireRole(?User $user, string|array $roles)
    {
        $user = self::requireUser($user);

        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }
        $roles = (array) $roles;

        $hasRole = $user->roles->contains(
            fn($role) => in_array($role->role_type, $roles, true)
        );

        if (!$hasRole) {
            throw new Exception('Insufficient permissions', 403);
        }

        return $user;
    }
}
