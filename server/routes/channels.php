<?php

use App\Models\Branch;
use Illuminate\Support\Facades\Broadcast;


Broadcast::routes([
    'middleware' => ['api', 'auth:sanctum'],
]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->uuid === (string) $id;
});

Broadcast::channel('Notification.{uuid}', function ($user, $uuid) {
    return (string) $user->uuid === (string) $uuid;
});

Broadcast::channel('qr.{token}', function ($user, string $token) {
    return $user !== null;
});

Broadcast::channel('Client.Messages.{uuid}', function ($user, string $uuid) {
    return (string) $user->uuid === (string) $uuid;
});

Broadcast::channel('Branch.Messages.{branchUuid}', function ($user, string $branchUuid) {
    $branch = Branch::where('uuid', $branchUuid)->first();

    if (!$branch || !$user->employee) {
        return false;
    }

    return $user->employee->employeeBranch()
        ->where('branch_id', $branch->branch_id)
        ->exists();
});
