<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SocialAccount;
use App\Models\OnboardingProgress;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to the provider's OAuth page.
     */
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the provider callback.
     */
    public function callback(string $provider): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => "Social login failed: " . $e->getMessage(),
            ]);
        }

        // Find existing social account
        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            $user = $socialAccount->user;
            Auth::login($user);
        } else {
            // Check if user with that email already exists
            $user = User::where('email', $socialUser->getEmail())->first();

            if (!$user) {
                // Generate a unique referral code
                do {
                    $referralCode = Str::random(8);
                } while (User::where('referral_code', $referralCode)->exists());

                // Create a new user (social logins skip OTP verification)
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'OAuth User',
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(Str::random(16)),
                    'email_verified_at' => now(),
                    'referral_code' => $referralCode,
                    'avatar_path' => $socialUser->getAvatar(),
                ]);

                // Create onboarding progress (with email already verified)
                OnboardingProgress::create([
                    'user_id' => $user->id,
                    'step_email_verified' => true,
                ]);

                AuditLogger::log('user.created.social', $user);
            }

            // Create and link social account
            SocialAccount::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'token' => $socialUser->token,
                'refresh_token' => $socialUser->refreshToken,
                'avatar_url' => $socialUser->getAvatar(),
            ]);

            Auth::login($user);
        }

        AuditLogger::log('user.login.social', $user);

        if ($user->requiresSubscription()) {
            return redirect()->route('pricing');
        }

        $destination = $user->defaultAuthenticatedPath();

        return redirect()->intended($destination);
    }
}
