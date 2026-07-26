<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Private channel for user-specific events (calls, signals, etc.).
| The client subscribes to: user.{userId}
|

| When debugging, check the Laravel log for authorization results.
|
*/

Broadcast::channel('user.{id}', function ($user, $id) {
    $authorized = (int) $user->id === (int) $id;

    Log::info('[Channels] Authorization check for user.{id}', [
        'user_id' => $user->id,
        'requested_id' => $id,
        'authorized' => $authorized,
    ]);

    return $authorized;
});
