<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LoginHistory;
use App\Services\OtpService;
use App\Services\NotificationDispatcher;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('auth/login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        // Check login lockout
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        // Check is_suspended BEFORE authentication attempt
        $user = User::where('email', $request->email)->first();
        if ($user && $user->is_suspended) {
            // Log block event to login history
            LoginHistory::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent(), 0, 500),
                'login_at' => now(),
                'status' => 'blocked',
                'failure_reason' => 'Account is suspended.',
            ]);

            throw ValidationException::withMessages([
                'email' => "Your account has been suspended. Reason: {$user->suspended_reason}",
            ]);
        }

        // Attempt login
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            if ($user) {
                // Log failed login event
                LoginHistory::create([
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr($request->userAgent(), 0, 500),
                    'login_at' => now(),
                    'status' => 'failed',
                    'failure_reason' => 'Invalid password.',
                ]);
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Authentication success
        RateLimiter::clear($throttleKey);
        $user = Auth::user();

        // Update login details
        $user->last_login_at = now();
        $user->last_login_ip = $request->ip();
        $user->save();

        // Log successful login event
        LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 500),
            'login_at' => now(),
            'status' => 'success',
        ]);

        // Audit log if admin
        if ($user->hasRole('Admin') || $user->hasRole('Super Admin')) {
            AuditLogger::log('admin.login', $user);
        }

        // Regenerate session
        $request->session()->regenerate();

        // Check 2FA
        if ($user->two_factor_confirmed_at !== null) {
            session()->put('2fa_verified', false);

            // Generate OTP
            $otp = OtpService::generate($user, 'login_2fa');

            // Dispatch notification
            NotificationDispatcher::dispatch($user, 'otp_login_2fa');

            return redirect()->route('verification.otp', ['purpose' => 'login_2fa']);
        }

        // Check subscription
        if (!$user->hasValidSubscription()) {
            return redirect()->route('pricing');
        }

        return redirect()->intended('/dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
