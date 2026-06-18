<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MagicLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MagicLinkController extends Controller
{
    /**
     * Handle rendering or processing of magic link requests.
     */
    public function send(Request $request)
    {
        if ($request->isMethod('get')) {
            return Inertia::render('auth/magic-link');
        }

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->input('email');
        $throttleKey = 'magic_link:' . $email;

        // Rate limit 3 per hour
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => ["Too many magic link requests. Please try again in {$seconds} seconds."],
            ]);
        }

        RateLimiter::hit($throttleKey, 3600);

        // Find user
        $user = User::where('email', $email)->first();
        if ($user) {
            // Generate token
            $token = \Illuminate\Support\Str::random(64);

            // Store in magic_links
            MagicLink::updateOrCreate(
                ['email' => $email],
                [
                    'token' => $token,
                    'expires_at' => now()->addMinutes(15),
                    'used_at' => null,
                ]
            );

            // Dispatch signed URL
            $signedUrl = URL::temporarySignedRoute(
                'magic-link.login',
                now()->addMinutes(15),
                ['email' => $email, 'token' => $token]
            );

            try {
                if (class_exists(\App\Mail\MagicLinkMail::class)) {
                    Mail::to($email)->send(new \App\Mail\MagicLinkMail($signedUrl));
                } else {
                    Mail::raw("Use this link to log in: {$signedUrl}", function ($message) use ($email) {
                        $message->to($email)->subject('Your Magic Login Link');
                    });
                }
            } catch (\Exception $e) {
                Log::error("Failed to send magic link email: " . $e->getMessage());
            }
        }

        // Return generic success to avoid email enumeration
        return back()->with('status', 'If your email is in our database, we have sent you a magic login link.');
    }

    /**
     * Log in the user using the magic link.
     */
    public function login(Request $request): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired signature.');
        }

        $email = $request->query('email');
        $token = $request->query('token');

        $magicLink = MagicLink::where('email', $email)
            ->where('token', $token)
            ->first();

        if (!$magicLink || $magicLink->used_at !== null || $magicLink->expires_at->isPast()) {
            abort(403, 'Invalid, expired, or already used magic link.');
        }

        // Mark as used
        $magicLink->update(['used_at' => now()]);

        // Find user
        $user = User::where('email', $email)->first();
        if (!$user) {
            abort(404, 'User not found.');
        }

        // Authenticate
        Auth::login($user);

        if ($user->requiresSubscription()) {
            return redirect()->route('pricing');
        }

        $destination = $user->defaultAuthenticatedPath();

        return redirect()->intended($destination);
    }
}
