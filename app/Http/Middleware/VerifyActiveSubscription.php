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

        if ($user->isStaff()) {
            return $next($request);
        }

        $channels = \App\Services\OtpService::getChannels();
        $emailVerifyEnabled = in_array('email', $channels);
        $phoneVerifyEnabled = (in_array('sms', $channels) || in_array('whatsapp', $channels)) && !empty($user->phone_number);

        // If email verification is enabled and not verified, redirect to OTP verification page
        if ($emailVerifyEnabled && $user->email_verified_at === null) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Your email address is not verified.'], 403)
                : redirect('/verify/otp?purpose=email_verify');
        }

        // If phone verification is enabled and not verified, redirect to OTP verification page
        if ($phoneVerifyEnabled && $user->phone_verified_at === null) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Your phone number is not verified.'], 403)
                : redirect('/verify/otp?purpose=phone_verify');
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
