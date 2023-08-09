<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyApiAuthentication
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->guest()) {
            return response()->json(['error' => 'Unauthenticated.', 'code' => 401], 401);
        }

        return $next($request);
    }
}
