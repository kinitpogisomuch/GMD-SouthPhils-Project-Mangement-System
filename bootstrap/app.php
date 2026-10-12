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
})
    ->withExceptions(function (Exceptions $exceptions): void {
        // A project can't be completed while its Financial Overview is incomplete — whichever code path
        // tried it, show the reason instead of an error page.
        $exceptions->render(function (\App\Exceptions\ProjectCompletionBlockedException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage(), 'missing' => $e->missing], 422);
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        });
    })->create();
