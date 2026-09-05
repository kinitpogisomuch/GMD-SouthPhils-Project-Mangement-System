<?php

use Illuminate\Support\Facades\Broadcast;

// This app uses session-based auth (session('role')/session('user_id')), not
// Laravel's Auth facade, so channel authorization checks the session directly
// instead of the usual $user parameter.
Broadcast::channel('inbox.{type}.{id}', function ($user, string $type, int $id) {
    return session('role') === $type && (int) session('user_id') === $id;
});
