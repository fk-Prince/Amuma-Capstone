<?php

namespace App\Repository;

use App\Models\Auth;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class UserRepository
{

    public function create(array $payload)
    {
        return User::create($payload);
    }

    public function findByField(string $column, string $value)
    {
        return User::where($column, $value)->first();
    }

    public function getAllUsersByBranchAndRole(string $branch_id, string $role_type)
    {
        return User::whereHas('roles', function ($q) use ($role_type, $branch_id) {
            $q->where('roles.role_type', $role_type)
                ->where('user_roles.branch_id', $branch_id)
                ->where('user_roles.is_active', 1);
        })->get();
    }

    public function updateOrCreate(object $payload)
    {
        return User::updateOrCreate(
            ['email' => $payload->getEmail()],
            [
                'first_name'      => explode(' ', $payload->getName())[0],
                'last_name'       => explode(' ', $payload->getName())[1] ?? '',
                'uuid'            => $payload->getId(),
                'provider'        => 'google',
                'password'        => null,
                'profile_picture' => $payload->getAvatar(),
            ]
        );
    }
}
