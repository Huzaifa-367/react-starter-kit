<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Services\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OtpController extends Controller
{
    /**
     * Display the OTP verification view.
     */
    public function show(Request $request): Response
    {
        $purpose = $request->query('purpose', 'email_verify');
        $user = Auth::user() ?: User::where('email', $request->query('email'))->first();
        
        $contact = '';
        if ($user) {
            if ($purpose === 'phone_verify') {
                $phone = $user->phone_number ?? '';
                $contact = strlen($phone) > 6 
                    ? substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 6) . substr($phone, -3) 
                    : $phone;
            } else {
                $email = $user->email ?? '';
                $parts = explode('@', $email);
                if (count($parts) === 2) {
                    $contact = substr($parts[0], 0, 2) . '***@' . substr($parts[1], 0, 2) . '***';
                } else {
                    $contact = $email;
                }
            }
        }

        return Inertia::render('auth/otp-verify', [
            'purpose' => $purpose,
            'contact' => $contact,
            'email' => $user?->email,
            'expires_in_seconds' => 600,
        ]);
    }

    /**
     * Verify the submitted OTP.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
            'purpose' => ['required', 'string', 'in:email_verify,phone_verify,login_2fa,password_reset'],
            'email' => ['nullable', 'email'],
        ]);

        $user = Auth::user() ?: User::where('email', $request->email)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        if (OtpService::isLockedOut($user)) {
            $seconds = OtpService::lockoutSecondsRemaining($user);
            throw ValidationException::withMessages([
                'code' => ["Too many verification attempts. Please try again in {$seconds} seconds."],
            ]);
        }

        $verified = OtpService::verify($user, $request->code, $request->purpose);
        if (!$verified) {
            if (OtpService::isLockedOut($user)) {
                $seconds = OtpService::lockoutSecondsRemaining($user);
                throw ValidationException::withMessages([
                    'code' => ["Too many verification attempts. Please try again in {$seconds} seconds."],
                ]);
            }
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired verification code.'],
            ]);
        }

        // Handle success by purpose
        switch ($request->purpose) {
            case 'email_verify':
                $user->email_verified_at = now();
                $user->save();

                if ($user->onboarding) {
                    $user->onboarding->update(['step_email_verified' => true]);
                }

                return redirect()->route('pricing');

            case 'phone_verify':
                $user->phone_verified_at = now();
                $user->save();

                if ($user->onboarding) {
                    $user->onboarding->update(['step_profile_completed' => true]);
                }

                return redirect()->back()->with('status', 'Phone number verified.');

            case 'login_2fa':
                session()->put('2fa_verified', true);
                return redirect()->intended('/dashboard');

            case 'password_reset':
                return redirect()->route('password.reset.form', [
                    'email' => $user->email,
                    'code' => $request->code
                ]);
        }

        return redirect()->route('dashboard');
    }

    /**
     * Resend the OTP code.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'purpose' => ['required', 'string'],
            'email' => ['nullable', 'email'],
        ]);

        $user = Auth::user() ?: User::where('email', $request->email)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        if (OtpService::isLockedOut($user)) {
            $seconds = OtpService::lockoutSecondsRemaining($user);
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => "Too many attempts. Locked out for {$seconds} seconds."
                ], 429);
            }
            throw ValidationException::withMessages([
                'code' => ["Too many attempts. Locked out for {$seconds} seconds."],
            ]);
        }

        OtpService::clear($user);
        $code = OtpService::generate($user, $request->purpose);

        // Dispatch notifications
        $event = 'otp_' . $request->purpose;
        NotificationDispatcher::dispatch($user, $event);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Verification code sent.',
                'expires_in_seconds' => 600,
            ]);
        }

        return back()->with('status', 'Verification code resent.');
    }
}
