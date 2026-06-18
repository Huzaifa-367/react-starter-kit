<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use App\Models\FcmToken;
use App\Services\OtpService;
use App\Services\NotificationDispatcher;
use App\Services\AuditLogger;
use App\Services\SubscriptionManager;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile immediately.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Cancel subscription immediately
        $sub = $user->subscriptions()->where('status', 'active')->first();
        if ($sub) {
            try {
                $subManager = new SubscriptionManager();
                $subManager->cancelImmediately($sub);
            } catch (\Exception $e) {
                Log::error("Failed to cancel subscription during account destruction: " . $e->getMessage());
            }
        }

        // Deactivate FCM tokens
        FcmToken::where('user_id', $user->id)->update(['is_active' => false]);

        // Audit log
        AuditLogger::log('user.deleted', $user);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Update the user's avatar image.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $user = $request->user();

        // Delete old avatar if present
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        // Store new avatar in public storage
        $path = $request->file('avatar')->store('avatars', 'public');

        $user->avatar_path = $path;
        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Avatar updated.')]);

        return back();
    }

    /**
     * Delete the user's avatar image.
     */
    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Avatar deleted.')]);

        return back();
    }

    /**
     * Update user's phone number and trigger OTP verification.
     */
    public function updatePhone(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/', 'unique:users,phone_number,' . $user->id],
        ]);

        $user->phone_number = $request->phone_number;
        $user->phone_verified_at = null;
        $user->save();

        // Generate and send phone verification OTP
        OtpService::generate($user, 'phone_verify');
        NotificationDispatcher::dispatch($user, 'otp_phone_verify');

        return redirect()->route('verification.otp', ['purpose' => 'phone_verify']);
    }

    /**
     * Request a secure account deletion via email validation.
     */
    public function requestDeletion(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = $request->user();

        // Generate temporary signed URL valid for 24 hours
        $confirmUrl = URL::temporarySignedRoute(
            'profile.deletion.confirm',
            now()->addHours(24),
            ['email' => $user->email]
        );

        try {
            if (class_exists(\App\Mail\AccountDeletionConfirmMail::class)) {
                Mail::to($user->email)->send(new \App\Mail\AccountDeletionConfirmMail($confirmUrl));
            } else {
                Mail::raw("To confirm your account deletion, click this link: {$confirmUrl}", function ($message) use ($user) {
                    $message->to($user->email)->subject('Confirm Account Deletion');
                });
            }
        } catch (\Exception $e) {
            Log::error("Failed to send account deletion confirmation email: " . $e->getMessage());
        }

        return back()->with('status', 'An account deletion confirmation link has been sent to your email.');
    }

    /**
     * Confirm account deletion via signed link validation.
     */
    public function confirmDeletion(Request $request): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired deletion confirmation link.');
        }

        $email = $request->query('email');
        $user = User::where('email', $email)->firstOrFail();

        // Cancel subscription immediately
        $sub = $user->subscriptions()->where('status', 'active')->first();
        if ($sub) {
            try {
                $subManager = new SubscriptionManager();
                $subManager->cancelImmediately($sub);
            } catch (\Exception $e) {
                Log::error("Failed to cancel subscription during account deletion: " . $e->getMessage());
            }
        }

        // Deactivate FCM tokens
        FcmToken::where('user_id', $user->id)->update(['is_active' => false]);

        // Audit log deletion
        AuditLogger::log('user.deleted', $user);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Your account has been successfully deleted.');
    }

    /**
     * Delegate GDPR export requests to the DataExportController.
     */
    public function requestExport(Request $request): RedirectResponse
    {
        return app(DataExportController::class)->store($request);
    }

    /**
     * Show the user's referrals and rewards.
     */
    public function referrals(Request $request): Response
    {
        $user = $request->user();

        // Get referral stats
        $stats = [
            'total' => $user->referralsSent()->count(),
            'pending' => $user->referralsSent()->where('status', 'pending')->count(),
            'converted' => $user->referralsSent()->where('status', 'converted')->count(),
            'rewarded' => $user->referralsSent()->where('status', 'rewarded')->count(),
        ];

        // Get referrals list with the referred user details
        $history = $user->referralsSent()
            ->with('referred')
            ->latest()
            ->get()
            ->map(function ($referral) {
                return [
                    'id' => $referral->id,
                    'referred_name' => $referral->referred->name ?? 'Guest User',
                    'referred_email' => $referral->referred ? substr($referral->referred->email, 0, 3) . '***@' . explode('@', $referral->referred->email)[1] : 'N/A',
                    'status' => $referral->status,
                    'reward_type' => $referral->reward_type,
                    'reward_value' => $referral->reward_value,
                    'created_at' => $referral->created_at->toDateString(),
                ];
            });

        return Inertia::render('profile/referrals', [
            'referral_code' => $user->referral_code,
            'stats' => $stats,
            'history' => $history,
        ]);
    }
}

