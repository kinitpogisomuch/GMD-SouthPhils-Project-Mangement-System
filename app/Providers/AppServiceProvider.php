<?php

namespace App\Providers;

use App\Mail\ResendTransport;
use App\Mail\SendGridTransport;
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
        // Local dev: Resend HTTP API
        Mail::extend('resend-http', function () {
            return new ResendTransport(config('services.resend.key', ''));
        });

        // Production: SendGrid HTTP API
        Mail::extend('sendgrid-http', function () {
            return new SendGridTransport(config('services.sendgrid.key', ''));
        });
    }
}
