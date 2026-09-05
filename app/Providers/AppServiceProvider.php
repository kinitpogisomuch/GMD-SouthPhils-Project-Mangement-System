<?php

namespace App\Providers;

use App\Mail\SendGridTransport;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Mail::extend('sendgrid-http', function () {
            return new SendGridTransport(config('services.sendgrid.key', ''));
        });

        // This app authenticates via plain session values (session('role')/
        // session('user_id')), not Laravel's Auth facade. Broadcasting's private
        // channel authorization, however, hard-requires $request->user() to be
        // truthy before it will even consult routes/channels.php's own check.
        // This guard bridges the two by wrapping the existing session into a
        // GenericUser, purely so that framework-level gate passes — the actual
        // per-channel authorization still happens in routes/channels.php.
        Auth::viaRequest('session-actor', function ($request) {
            $role = $request->session()->get('role');
            $id   = $request->session()->get('user_id');

            return ($role && $id) ? new GenericUser(['id' => $id, 'role' => $role]) : null;
        });
    }
}
