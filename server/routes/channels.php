<?php

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

Broadcast::channel('User.Messages.{uuid}', function ($user, string $uuid) {
    return (string) $user->uuid === (string) $uuid;
});
