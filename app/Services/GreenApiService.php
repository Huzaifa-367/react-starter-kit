<?php

namespace App\Services;

use App\Models\User;
use App\Models\Setting;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;

class GreenApiService
{
    /**
     * Send message using Green API and log to database.
     */
    public function sendMessage(string $phoneNumber, string $message): bool
    {
        return $this->sendMessageWithType($phoneNumber, $message, 'custom');
    }

    /**
     * Internal method to send message with a specific type log.
     */
    protected function sendMessageWithType(string $phoneNumber, string $message, string $type): bool
    {
        $url = Setting::get('green_api_url');
        $idInstance = Setting::get('green_api_id_instance');
        $tokenInstance = Setting::get('green_api_token_instance');

        if (empty($url) || empty($idInstance) || empty($tokenInstance)) {
            return false;
        }

        // Clean up phone number: only digits
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (empty($cleanPhone)) {
            return false;
        }

        // WhatsApp chatId requires @c.us for single contacts
        $chatId = str_contains($cleanPhone, '@') ? $cleanPhone : "{$cleanPhone}@c.us";

        // Find user by phone number for logging association if possible
        $user = User::where('phone_number', $phoneNumber)->first();
        $userId = $user ? $user->id : null;

        try {
            $endpoint = rtrim($url, '/') . "/waInstance{$idInstance}/sendMessage/{$tokenInstance}";
            $response = Http::timeout(10)->post($endpoint, [
                'chatId' => $chatId,
                'message' => $message,
            ]);

            $success = $response->successful();
            $errorMsg = $success ? null : "HTTP status: " . $response->status() . " - Body: " . $response->body();

            NotificationLog::create([
                'user_id' => $userId,
                'channel' => 'whatsapp',
                'type' => $type,
                'recipient' => $phoneNumber,
                'status' => $success ? 'sent' : 'failed',
                'error_message' => $errorMsg,
                'sent_at' => $success ? now() : null,
            ]);

            return $success;

        } catch (\Exception $e) {
            NotificationLog::create([
                'user_id' => $userId,
                'channel' => 'whatsapp',
                'type' => $type,
                'recipient' => $phoneNumber,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'sent_at' => null,
            ]);

            return false;
        }
    }

    public function sendOtp(string $phone, string $code): bool
    {
        $message = "Your verification code is: {$code}. Valid for 10 minutes.";
        return $this->sendMessageWithType($phone, $message, 'otp_phone_verify');
    }

    public function sendTrialEnding(string $phone, string $planName, string $endsIn): bool
    {
        $message = "Your trial for plan {$planName} is ending in {$endsIn}.";
        return $this->sendMessageWithType($phone, $message, 'subscription_trial_end');
    }

    public function sendRenewalUpcoming(string $phone, string $planName, string $renewsOn, string $price): bool
    {
        $message = "Your subscription for plan {$planName} will renew on {$renewsOn} for {$price}.";
        return $this->sendMessageWithType($phone, $message, 'subscription_renewal');
    }

    public function sendGraceWarning(string $phone, int $graceDaysLeft): bool
    {
        $message = "Your subscription payment failed. You have {$graceDaysLeft} days of grace period remaining before access is suspended.";
        return $this->sendMessageWithType($phone, $message, 'subscription_grace');
    }

    public function sendSubscriptionExpired(string $phone): bool
    {
        $message = "Your subscription has expired. Please update your billing information to resume access.";
        return $this->sendMessageWithType($phone, $message, 'subscription_expired');
    }

    public function sendSuspensionNotice(string $phone, ?string $reason): bool
    {
        $message = "Your account has been suspended." . ($reason ? " Reason: {$reason}" : "");
        return $this->sendMessageWithType($phone, $message, 'account_suspended');
    }

    public function sendInvitation(string $phone, string $inviteLink): bool
    {
        $message = "You have been invited to join. Click here to accept: {$inviteLink}";
        return $this->sendMessageWithType($phone, $message, 'invitation_sent');
    }
}
