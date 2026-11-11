<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*');
    $middleware->alias([
        'role'             => \App\Http\Middleware\RoleMiddleware::class,
        'profile.complete' => \App\Http\Middleware\RequireProfileComplete::class,
        'no.back'          => \App\Http\Middleware\PreventBackHistory::class,
    ]);
    // One-time form tokens: a repeated submit (double click, refresh, simultaneous request) is refused
    $middleware->web(append: [\App\Http\Middleware\PreventDuplicateSubmission::class]);
    // The payment gateway posts here from its own servers; the request is authenticated by its signature instead
    $middleware->validateCsrfTokens(except: ['webhooks/paymongo']);
})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
