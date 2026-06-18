<?php

namespace App\Services;

use App\Models\User;
use App\Models\Setting;
use App\Models\NotificationLog;
use Twilio\Rest\Client;

class TwilioService
{
    /**
     * Send SMS using Twilio and log to database.
     */
    public function sendSms(string $phoneNumber, string $message): bool
    {
        return $this->sendSmsWithType($phoneNumber, $message, 'custom');
    }

    /**
     * Internal method to send SMS with a specific type log.
     */
    protected function sendSmsWithType(string $phoneNumber, string $message, string $type): bool
    {
        $sid = Setting::get('twilio_account_sid');
        $token = Setting::get('twilio_auth_token');
        $from = Setting::get('twilio_from_number');

        if (empty($sid) || empty($token) || empty($from)) {
            return false;
        }

        // Find user by phone number for logging association if possible
        $user = User::where('phone_number', $phoneNumber)->first();
        $userId = $user ? $user->id : null;

        try {
            $client = new Client($sid, $token);
            $client->messages->create($phoneNumber, [
                'from' => $from,
                'body' => $message,
            ]);

            NotificationLog::create([
                'user_id' => $userId,
                'channel' => 'sms',
                'type' => $type,
                'recipient' => $phoneNumber,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return true;

        } catch (\Exception $e) {
            NotificationLog::create([
                'user_id' => $userId,
                'channel' => 'sms',
                'type' => $type,
                'recipient' => $phoneNumber,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'sent_at' => null,
            ]);

            return false;
        }
    }

    /**
     * Send OTP message.
     */
    public function sendOtp(string $phone, string $code): bool
    {
        $message = "Your verification code is: {$code}. Valid for 10 minutes.";
        return $this->sendSmsWithType($phone, $message, 'otp_phone_verify');
    }
}
