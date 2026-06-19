<?php

namespace App\Services;

use App\Models\User;
use App\Models\Setting;
use App\Models\NotificationLog;
use GreenApi\RestApi\GreenApiClient;

class GreenApiService
{
    /**
     * Get initialized GreenApiClient.
     */
    protected function getClient(): ?GreenApiClient
    {
        $idInstance = Setting::get('green_api_id_instance') ?: config('services.green_api.id_instance');
        $tokenInstance = Setting::get('green_api_token_instance') ?: config('services.green_api.token_instance');

        if (empty($idInstance) || empty($tokenInstance)) {
            return null;
        }

        return app()->make(GreenApiClient::class);
    }

    /**
     * Synchronize WhatsApp session settings (phone and avatar) from Green API.
     */
    public function syncSessionSettings(): bool
    {
        $client = $this->getClient();
        if (!$client) {
            return false;
        }

        try {
            $response = $client->account->getWaSettings();

            if ($response && $response->code === 200 && isset($response->data)) {
                $phone = $response->data->phone ?? '';
                $avatar = $response->data->avatar ?? '';

                Setting::set('green_api_phone', $phone);
                Setting::set('green_api_avatar', $avatar);

                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

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
        $client = $this->getClient();
        if (!$client) {
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
            $result = $client->sending->sendMessage($chatId, $message);

            $success = ($result && $result->code === 200);
            $errorMsg = $success ? null : "Code: " . ($result->code ?? 'unknown') . " - Error: " . json_encode($result->error ?? 'none');

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
