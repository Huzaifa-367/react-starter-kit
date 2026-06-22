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
        $routeName = $request->route()?->getName();
        \Illuminate\Support\Facades\Log::info('VerifyActiveSubscription check at top:', [
            'route_name' => $routeName,
            'url' => $request->fullUrl(),
            'user' => Auth::user()?->email,
        ]);

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

        if ($routeName && (
            $routeName === 'pricing' ||
            $routeName === 'pricing.subscribed' ||
            str_starts_with($routeName, 'billing.')
        )) {
            return $next($request);
        }

        // If no valid subscription, redirect to pricing page or return 403 JSON
        if (!$user->hasValidSubscription()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Active subscription required.'], 403)
                : redirect('/pricing');
        }

        return $next($request);
    }
}
