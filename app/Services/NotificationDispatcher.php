<?php

namespace App\Services;

use App\Models\User;
use App\Models\FcmToken;
use App\Models\NotificationLog;
use App\Models\UserNotification;
use App\Services\OtpService;
use App\Services\GreenApiService;
use App\Services\TwilioService;
use App\Services\FcmService;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationDispatcher
{
    /**
     * Map events to target channels configuration.
     */
    protected static array $routing = [
        'otp_email_verify'       => ['email' => true,  'whatsapp' => true,  'fcm' => true,  'sms' => true,  'in_app' => false],
        'otp_login_2fa'          => ['email' => true,  'whatsapp' => true,  'fcm' => true,  'sms' => true,  'in_app' => false],
        'otp_phone_verify'       => ['email' => false, 'whatsapp' => true,  'fcm' => true,  'sms' => true,  'in_app' => false],
        'otp_password_reset'     => ['email' => true,  'whatsapp' => true,  'fcm' => true,  'sms' => true,  'in_app' => false],
        'subscription_activated' => ['email' => true,  'whatsapp' => true,  'fcm' => true,  'sms' => true,  'in_app' => true],
        'subscription_trial_end' => ['email' => true,  'whatsapp' => true,  'fcm' => true,  'sms' => true,  'in_app' => true],
        'subscription_renewal'   => ['email' => true,  'whatsapp' => true,  'fcm' => true,  'sms' => true,  'in_app' => true],
        'subscription_renewed'   => ['email' => true,  'whatsapp' => true,  'fcm' => true,  'sms' => true,  'in_app' => true],
        'subscription_grace'     => ['email' => true,  'whatsapp' => true,  'fcm' => true,  'sms' => true,  'in_app' => true],
        'subscription_expired'   => ['email' => true,  'whatsapp' => true,  'fcm' => true,  'sms' => true,  'in_app' => true],
        'subscription_canceled'  => ['email' => true,  'whatsapp' => true,  'fcm' => true,  'sms' => true,  'in_app' => true],
        'account_suspended'      => ['email' => true,  'whatsapp' => true,  'fcm' => true,  'sms' => true,  'in_app' => false],
        'invitation_sent'        => ['email' => true,  'whatsapp' => true,  'fcm' => false, 'sms' => true,  'in_app' => false],
        'dunning_retry'          => ['email' => true,  'whatsapp' => true,  'fcm' => true,  'sms' => true,  'in_app' => true],
    ];

    /**
     * Map events to App\Mail classes.
     */
    protected static array $mailClasses = [
        'otp_email_verify'       => 'App\Mail\OtpMail',
        'otp_login_2fa'          => 'App\Mail\OtpMail',
        'otp_password_reset'     => 'App\Mail\OtpMail',
        'subscription_activated' => 'App\Mail\SubscriptionActivatedMail',
        'subscription_trial_end' => 'App\Mail\TrialEndingMail',
        'subscription_renewal'   => 'App\Mail\RenewalUpcomingMail',
        'subscription_renewed'   => 'App\Mail\SubscriptionRenewedMail',
        'subscription_grace'     => 'App\Mail\GraceWarningMail',
        'subscription_expired'   => 'App\Mail\SubscriptionExpiredMail',
        'subscription_canceled'  => 'App\Mail\SubscriptionCanceledMail',
        'account_suspended'      => 'App\Mail\AccountSuspendedMail',
        'invitation_sent'        => 'App\Mail\InvitationMail',
        'dunning_retry'          => 'App\Mail\DunningRetryMail',
    ];

    /**
     * Dispatch notification for a given event to all configured channels.
     */
    public static function dispatch($user, string $event, array $options = []): void
    {
        if (!isset(self::$routing[$event])) {
            Log::warning("Notification event '{$event}' is not registered in routing table.");
            return;
        }

        $routes = self::$routing[$event];
        $isOtpEvent = str_starts_with($event, 'otp_');

        // Extract placeholders
        $sub = ($user instanceof User) ? $user->getActiveSubscription() : null;
        $otpCode = $options['code'] ?? $options['otp_code'] ?? (($user instanceof User) ? OtpService::getPlainOtp($user->id) : null) ?? '000000';
        
        // Ensure OTP code is serialized with options for queued mailables
        if (!isset($options['code'])) {
            $options['code'] = $otpCode;
        }
        if (!isset($options['otp_code'])) {
            $options['otp_code'] = $otpCode;
        }
        $planName = $options['plan_name'] ?? $sub?->plan?->name ?? 'Free Plan';
        $endsIn = $options['ends_in'] ?? ($sub?->trial_ends_at ? $sub->trial_ends_at->diffForHumans() : '7 days');
        $renewsOn = $options['renews_on'] ?? ($sub?->ends_at ? $sub->ends_at->toDateString() : 'N/A');
        $price = $options['price'] ?? ($sub?->plan ? '$' . $sub->plan->price : '$0.00');
        $graceDaysLeft = $options['grace_days_left'] ?? $options['days'] ?? $sub?->plan?->grace_days ?? 7;
        $reason = $options['reason'] ?? ($user->suspended_reason ?? null) ?? 'Violation of terms';
        $inviteLink = $options['invite_link'] ?? $options['link'] ?? url('/register');

        // Prepare Title and Body
        $titleBody = self::getTitleAndBody($event, $planName, $endsIn, $renewsOn, $price, $graceDaysLeft, $reason, $inviteLink, $otpCode);
        $title = $titleBody['title'];
        $body = $titleBody['body'];

        $userId = (!empty($user->id)) ? $user->id : null;

        // 1. Email Channel
        if ($routes['email'] && !empty($user->email) && self::isEmailConfigured()) {
            $skipEmail = false;

            // Check if OTP event and email is not in default channels
            if ($isOtpEvent && !in_array('email', OtpService::getChannels())) {
                $skipEmail = true;
            }

            // Check if user has bounced email
            if (($user->email_bounced_at ?? null) !== null) {
                $skipEmail = true;
                Log::info("Skipping email for user " . ($userId ?? 0) . " because email has bounced.");
            }

            if (!$skipEmail) {
                $mailClass = self::$mailClasses[$event] ?? null;
                if ($mailClass && class_exists($mailClass)) {
                    try {
                        Mail::to($user->email)->send(new $mailClass($user, $options));
                        NotificationLog::create([
                            'user_id' => $userId,
                            'channel' => 'email',
                            'type' => $event,
                            'recipient' => $user->email,
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        NotificationLog::create([
                            'user_id' => $userId,
                            'channel' => 'email',
                            'type' => $event,
                            'recipient' => $user->email,
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                        ]);
                    }
                } else {
                    // Fallback log for dev or if mail class isn't created yet
                    Log::info("Mail class '{$mailClass}' not found. Simulated sending email to {$user->email}.");
                    NotificationLog::create([
                        'user_id' => $userId,
                        'channel' => 'email',
                        'type' => $event,
                        'recipient' => $user->email,
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);
                }
            }
        }

        // 2. WhatsApp Channel
        if ($routes['whatsapp'] && !empty($user->phone_number) && self::isWhatsappConfigured()) {
            $skipWa = false;
            if ($isOtpEvent && !in_array('whatsapp', OtpService::getChannels())) {
                $skipWa = true;
            }

            if (!$skipWa) {
                $greenApi = new GreenApiService();
                $sent = false;

                if ($event === 'otp_email_verify' || $event === 'otp_login_2fa' || $event === 'otp_phone_verify' || $event === 'otp_password_reset') {
                    $sent = $greenApi->sendOtp($user->phone_number, $otpCode);
                } elseif ($event === 'subscription_trial_end') {
                    $sent = $greenApi->sendTrialEnding($user->phone_number, $planName, $endsIn);
                } elseif ($event === 'subscription_renewal') {
                    $sent = $greenApi->sendRenewalUpcoming($user->phone_number, $planName, $renewsOn, $price);
                } elseif ($event === 'subscription_grace') {
                    $sent = $greenApi->sendGraceWarning($user->phone_number, $graceDaysLeft);
                } elseif ($event === 'subscription_expired') {
                    $sent = $greenApi->sendSubscriptionExpired($user->phone_number);
                } elseif ($event === 'account_suspended') {
                    $sent = $greenApi->sendSuspensionNotice($user->phone_number, $reason);
                } elseif ($event === 'invitation_sent') {
                    $sent = $greenApi->sendInvitation($user->phone_number, $inviteLink);
                } else {
                    $sent = $greenApi->sendMessage($user->phone_number, $body);
                }

                // GreenApiService internally logs to notification_logs, no need to log twice.
            }
        }

        // 3. SMS Channel
        if ($routes['sms'] && !empty($user->phone_number) && self::isSmsConfigured()) {
            $skipSms = false;
            if ($isOtpEvent && !in_array('sms', OtpService::getChannels())) {
                $skipSms = true;
            }

            if (!$skipSms) {
                $twilio = new TwilioService();
                if ($isOtpEvent) {
                    $twilio->sendOtp($user->phone_number, $otpCode);
                } else {
                    $twilio->sendSms($user->phone_number, $body);
                }
                // TwilioService internally logs to notification_logs, no need to log twice.
            }
        }

        // 4. FCM Channel
        if ($routes['fcm']) {
            $skipFcm = false;
            if ($isOtpEvent && !in_array('fcm', OtpService::getChannels())) {
                $skipFcm = true;
            }

            // Skip if user does not have active FCM tokens or is a dummy user
            if (empty($userId) || !FcmToken::where('user_id', $userId)->active()->exists()) {
                $skipFcm = true;
            }

            if (!$skipFcm && ($user instanceof User)) {
                $fcm = new FcmService();
                $fcm->sendToUser($user, $title, $body, ['type' => $event]);
                // FcmService internally logs to notification_logs.
            }
        }

        // 5. In-App Notification Channel
        if ($routes['in_app'] && !empty($userId)) {
            try {
                UserNotification::create([
                    'user_id' => $userId,
                    'type' => $event,
                    'title' => $title,
                    'body' => $body,
                    'action_url' => $options['action_url'] ?? null,
                    'data' => $options['data'] ?? null,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to create in-app notification: " . $e->getMessage());
            }
        }
    }

    /**
     * Map events to localized or formatted title/body text.
     */
    protected static function getTitleAndBody(
        string $event,
        string $planName,
        string $endsIn,
        string $renewsOn,
        string $price,
        int $graceDaysLeft,
        string $reason,
        string $inviteLink,
        string $otpCode
    ): array {
        switch ($event) {
            case 'otp_email_verify':
                return [
                    'title' => 'Verify Your Email',
                    'body' => "Your verification code is: {$otpCode}. Valid for 10 minutes."
                ];
            case 'otp_login_2fa':
                return [
                    'title' => 'Two-Factor Authentication Code',
                    'body' => "Your 2FA verification code is: {$otpCode}. Valid for 10 minutes."
                ];
            case 'otp_phone_verify':
                return [
                    'title' => 'Verify Your Phone',
                    'body' => "Your verification code is: {$otpCode}. Valid for 10 minutes."
                ];
            case 'otp_password_reset':
                return [
                    'title' => 'Reset Your Password',
                    'body' => "Your password reset verification code is: {$otpCode}. Valid for 10 minutes."
                ];
            case 'subscription_activated':
                return [
                    'title' => 'Subscription Activated',
                    'body' => "Your subscription to plan '{$planName}' is now active!"
                ];
            case 'subscription_trial_end':
                return [
                    'title' => 'Trial Ending Soon',
                    'body' => "Your trial for plan '{$planName}' is ending in {$endsIn}. Please update your payment information to stay subscribed."
                ];
            case 'subscription_renewal':
                return [
                    'title' => 'Subscription Renewal Upcoming',
                    'body' => "Your subscription for plan '{$planName}' will renew on {$renewsOn} for {$price}."
                ];
            case 'subscription_renewed':
                return [
                    'title' => 'Subscription Renewed',
                    'body' => "Your subscription for plan '{$planName}' was successfully renewed!"
                ];
            case 'subscription_grace':
                return [
                    'title' => 'Payment Failed - Grace Period Active',
                    'body' => "Your subscription payment failed. You have {$graceDaysLeft} days of grace period remaining before access is suspended."
                ];
            case 'subscription_expired':
                return [
                    'title' => 'Subscription Expired',
                    'body' => "Your subscription has expired. Please update your billing information to resume access."
                ];
            case 'subscription_canceled':
                return [
                    'title' => 'Subscription Canceled',
                    'body' => "Your subscription has been canceled. You will retain access until the end of the billing period."
                ];
            case 'account_suspended':
                return [
                    'title' => 'Account Suspended',
                    'body' => "Your account has been suspended. Reason: {$reason}"
                ];
            case 'invitation_sent':
                return [
                    'title' => "You've Been Invited!",
                    'body' => "You have been invited to join. Click here to register: {$inviteLink}"
                ];
            case 'dunning_retry':
                return [
                    'title' => 'Payment Retry Scheduled',
                    'body' => "We will retry your failed subscription payment soon. Please ensure your payment card has sufficient funds."
                ];
            default:
                return [
                    'title' => 'Notification',
                    'body' => 'You have received a new notification.'
                ];
        }
    }

    /**
     * Check if email channel is configured.
     */
    public static function isEmailConfigured(): bool
    {
        $mailer = config('mail.default');
        if (empty($mailer)) {
            return false;
        }
        if ($mailer === 'smtp') {
            $host = config('mail.mailers.smtp.host') ?: Setting::get('mail_host');
            return !empty($host);
        }
        return true;
    }

    /**
     * Check if WhatsApp channel is configured.
     */
    public static function isWhatsappConfigured(): bool
    {
        $idInstance = Setting::get('green_api_id_instance') ?: config('services.green_api.id_instance');
        $tokenInstance = Setting::get('green_api_token_instance') ?: config('services.green_api.token_instance');
        return !empty($idInstance) && !empty($tokenInstance);
    }

    /**
     * Check if SMS/Twilio channel is configured.
     */
    public static function isSmsConfigured(): bool
    {
        $sid = Setting::get('twilio_account_sid') ?: config('services.twilio.sid');
        $token = Setting::get('twilio_auth_token') ?: config('services.twilio.token');
        $from = Setting::get('twilio_from_number') ?: config('services.twilio.from');
        return !empty($sid) && !empty($token) && !empty($from);
    }
}
