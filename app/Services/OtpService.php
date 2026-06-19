<?php

namespace App\Services;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    const EXPIRY_MINUTES   = 10;
    const MAX_ATTEMPTS     = 5;
    const LOCKOUT_MINUTES  = 15;

    /**
     * Static registry to temporarily hold plain OTP codes in-memory during request lifecycle.
     */
    protected static array $plainOtps = [];

    public static function getPlainOtp(int $userId): ?string
    {
        return self::$plainOtps[$userId] ?? null;
    }

    public static function setPlainOtp(int $userId, string $code): void
    {
        self::$plainOtps[$userId] = $code;
    }

    public static function clearPlainOtp(int $userId): void
    {
        unset(self::$plainOtps[$userId]);
    }

    /**
     * Generate 6-digit OTP, store hashed in DB, set temporary plain code in static registry, and return plain OTP.
     */
    public static function generate(User $user, string $purpose): string
    {
        // Generate a 6-digit numeric OTP
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store hashed in database
        $user->otp_code = Hash::make($code);
        $user->otp_expires_at = now()->addMinutes(self::EXPIRY_MINUTES);
        $user->otp_purpose = $purpose;
        $user->save();

        \Illuminate\Support\Facades\Log::info("Generated OTP for {$user->email}: {$code}");

        // Save plain OTP in static registry for request lifecycle access (e.g. by NotificationDispatcher)
        self::setPlainOtp($user->id, $code);

        return $code;
    }

    /**
     * Verify submitted code. Lock out user on the 5th failed attempt.
     */
    public static function verify(User $user, string $code, string $purpose): bool
    {
        if (self::isLockedOut($user)) {
            return false;
        }

        $otpCode = $user->otp_code;
        $otpExpiresAt = $user->otp_expires_at;
        $otpPurpose = $user->otp_purpose;

        // Check if OTP is present, not expired, and purpose matches
        if ($otpCode && $otpPurpose === $purpose && $otpExpiresAt && now()->lt($otpExpiresAt)) {
            if (Hash::check($code, $otpCode)) {
                // Verification successful
                self::clear($user);
                Cache::forget("otp_fail:{$user->id}");
                Cache::forget("otp_lockout:{$user->id}");
                return true;
            }
        }

        // Increment failed attempts
        $fails = (int) Cache::get("otp_fail:{$user->id}", 0) + 1;
        Cache::put("otp_fail:{$user->id}", $fails, now()->addHours(1));

        if ($fails >= self::MAX_ATTEMPTS) {
            Cache::put(
                "otp_lockout:{$user->id}",
                now()->addMinutes(self::LOCKOUT_MINUTES)->timestamp,
                now()->addMinutes(self::LOCKOUT_MINUTES)
            );
        }

        return false;
    }

    /**
     * Check if user is currently locked out from OTP verification.
     */
    public static function isLockedOut(User $user): bool
    {
        return Cache::has("otp_lockout:{$user->id}");
    }

    /**
     * Get remaining lockout time in seconds.
     */
    public static function lockoutSecondsRemaining(User $user): int
    {
        $lockoutTime = Cache::get("otp_lockout:{$user->id}");
        if (!$lockoutTime) {
            return 0;
        }
        return max(0, $lockoutTime - now()->timestamp);
    }

    /**
     * Clear OTP fields in the database.
     */
    public static function clear(User $user): void
    {
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->otp_purpose = null;
        $user->save();

        self::clearPlainOtp($user->id);
    }

    /**
     * Which channels to use for OTP (reads admin settings).
     * Returns subset of ['email','whatsapp','sms','fcm']
     */
    public static function getChannels(): array
    {
        return Setting::get('otp_default_channels', ['email']);
    }
}
