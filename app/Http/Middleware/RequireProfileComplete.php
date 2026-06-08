<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireProfileComplete
{
    public function handle(Request $request, Closure $next)
    {
        if (session('user_id') && session('first_login') === true) {
            return redirect()->route('setup.credentials');
        }

        return $next($request);
    }
}
