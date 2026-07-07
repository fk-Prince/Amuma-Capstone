<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;


Broadcast::routes([
    'middleware' => ['api', 'auth:sanctum'],
]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    Log::info($user);
    return (string) $user->uuid === (string) $id;
});

Broadcast::channel('Notification.{uuid}', function ($user, $uuid) {
    return (string) $user->uuid === (string) $uuid;
});
