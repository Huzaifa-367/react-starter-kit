<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\OtpService;
use App\Services\NotificationDispatcher;
use App\Services\PasswordHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    /**
     * Display the forgot password view.
     */
    public function showLinkRequestForm(): Response
    {
        return Inertia::render('auth/forgot-password');
    }

    /**
     * Handle the forgot password request (send OTP).
     */
    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->input('email');
        $throttleKey = 'forgot_password:' . $email;

        // Rate limit 3 attempts per minute
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => ["Too many password reset requests. Please try again in {$seconds} seconds."],
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        $user = User::where('email', $email)->first();
        if ($user) {
            // Generate OTP
            $otp = OtpService::generate($user, 'password_reset');

            // Dispatch notification
            NotificationDispatcher::dispatch($user, 'otp_password_reset');
        }

        // Return generic message to avoid email enumeration
        return redirect()->route('password.reset.form', ['email' => $email])
            ->with('status', 'If your email exists, we have sent a 6-digit password reset code.');
    }

    /**
     * Display the reset password view.
     */
    public function showResetForm(Request $request): Response
    {
        return Inertia::render('auth/reset-password', [
            'email' => $request->query('email'),
            'code' => $request->query('code'),
        ]);
    }

    /**
     * Handle the password reset submission.
     */
    public function resetPassword(Request $request, PasswordHistoryService $passwordHistoryService): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', new StrongPassword()],
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        // Verify OTP
        if (OtpService::isLockedOut($user)) {
            $seconds = OtpService::lockoutSecondsRemaining($user);
            throw ValidationException::withMessages([
                'code' => ["Too many attempts. Locked out for {$seconds} seconds."],
            ]);
        }

        $verified = OtpService::verify($user, $request->code, 'password_reset');
        if (!$verified) {
            if (OtpService::isLockedOut($user)) {
                $seconds = OtpService::lockoutSecondsRemaining($user);
                throw ValidationException::withMessages([
                    'code' => ["Too many attempts. Locked out for {$seconds} seconds."],
                ]);
            }
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired reset code.'],
            ]);
        }

        // Check password history reuse
        if ($passwordHistoryService->check($user, $request->password)) {
            throw ValidationException::withMessages([
                'password' => ['You cannot reuse any of your last 5 passwords.'],
            ]);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        // Record in password history
        $passwordHistoryService->record($user, $user->password);

        // Revoke database sessions
        if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return redirect()->route('login')->with('status', 'Your password has been reset successfully.');
    }
}
