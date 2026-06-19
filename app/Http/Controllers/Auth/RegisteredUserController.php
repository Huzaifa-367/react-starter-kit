<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OnboardingProgress;
use App\Rules\StrongPassword;
use App\Services\OtpService;
use App\Services\NotificationDispatcher;
use App\Services\AuditLogger;
use App\Services\PasswordHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request, PasswordHistoryService $passwordHistoryService): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', new StrongPassword()],
            'phone_number' => ['nullable', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'terms' => ['required', 'accepted'],
        ]);

        // Check duplicate unverified email
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser) {
            if ($existingUser->email_verified_at === null) {
                // Regenerate OTP and redirect
                $otp = OtpService::generate($existingUser, 'email_verify');
                NotificationDispatcher::dispatch($existingUser, 'otp_email_verify');
                Auth::login($existingUser);
                return redirect()->route('verification.otp', ['purpose' => 'email_verify']);
            }

            // If verified, standard unique validation error
            $request->validate([
                'email' => ['unique:users'],
            ]);
        }

        // Generate a unique referral code
        do {
            $referralCode = Str::random(8);
        } while (User::where('referral_code', $referralCode)->exists());

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'referral_code' => $referralCode,
        ]);

        // Assign role 'User (Free)'
        if (\Spatie\Permission\Models\Role::where('name', 'User (Free)')->exists()) {
            $user->assignRole('User (Free)');
        } else if (!app()->environment('production')) {
            \Spatie\Permission\Models\Role::create(['name' => 'User (Free)', 'guard_name' => 'web']);
            $user->assignRole('User (Free)');
        }

        // Create onboarding progress
        OnboardingProgress::create([
            'user_id' => $user->id,
        ]);

        // Record in password history
        $passwordHistoryService->record($user, $user->password);

        // Check invitation token in query param
        $invitationToken = $request->query('invitation_token');
        if ($invitationToken) {
            $invitation = \OffloadProject\InviteOnly\Models\Invitation::where('token', $invitationToken)
                ->where('status', \OffloadProject\InviteOnly\Enums\InvitationStatus::Pending)
                ->first();
            if ($invitation) {
                $invitation->markAsAccepted($user);
            }
        }

        // Check referral link code
        $refCode = $request->query('ref');
        if ($refCode) {
            $referrer = User::where('referral_code', $refCode)->first();
            if ($referrer) {
                \App\Models\Referral::create([
                    'referrer_id' => $referrer->id,
                    'referred_id' => $user->id,
                    'code' => $refCode,
                    'status' => 'pending',
                    'reward_type' => \App\Models\Setting::get('referral_reward_type', 'none'),
                    'reward_value' => \App\Models\Setting::get('referral_reward_value', 0),
                ]);
            }
        }

        // Login the user
        Auth::login($user);

        // Log audit trail
        AuditLogger::log('user.created', $user);

        $channels = OtpService::getChannels();
        $emailVerifyEnabled = in_array('email', $channels);
        $phoneVerifyEnabled = (in_array('sms', $channels) || in_array('whatsapp', $channels)) && !empty($user->phone_number);

        if ($emailVerifyEnabled) {
            OtpService::generate($user, 'email_verify');
            NotificationDispatcher::dispatch($user, 'otp_email_verify');
            return redirect()->route('verification.otp', ['purpose' => 'email_verify']);
        }

        if ($phoneVerifyEnabled) {
            OtpService::generate($user, 'phone_verify');
            NotificationDispatcher::dispatch($user, 'otp_phone_verify');
            return redirect()->route('verification.otp', ['purpose' => 'phone_verify']);
        }

        return redirect()->route('pricing');
    }
}
