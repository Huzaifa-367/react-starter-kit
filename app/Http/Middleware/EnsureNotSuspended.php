<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureNotSuspended
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->is_suspended) {
            $reason = Auth::user()->suspended_reason ?? 'Your account has been suspended.';

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => $reason], 403)
                : redirect()->route('login')->withErrors(['email' => $reason]);
        }

        return $next($request);
    }
}
