<?php

namespace App\Providers;

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
        Mail::extend('sendgrid-http', function () {
            return new SendGridTransport(config('services.sendgrid.key', ''));
        });
    }
}
