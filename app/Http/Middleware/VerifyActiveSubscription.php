<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class VerifyActiveSubscription
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Skip check for Admins and Super Admins
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return $next($request);
        }

        // If email not verified, redirect to OTP verification page
        if (!$user->email_verified_at) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Your email address is not verified.'], 403)
                : redirect('/verify/otp?purpose=email_verify');
        }

        // If no valid subscription, redirect to pricing page
        if (!$user->hasValidSubscription()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Active subscription required.'], 402)
                : redirect('/pricing');
        }

        return $next($request);
    }
}
