<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use App\Models\NotificationLog;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailBounceController extends Controller
{
    /**
     * Handle incoming email bounce webhook.
     */
    public function handle(Request $request): \Illuminate\Http\JsonResponse
    {
        $mailProvider = Setting::get('mail_provider') ?: config('mail.default', 'smtp');
        $payload = $request->all();

        $bouncedEmails = [];
        $isHardBounce = false;

        Log::info("Received email bounce webhook. Provider: {$mailProvider}");

        // Parse bounce payload depending on mail provider setting format
        switch (strtolower($mailProvider)) {
            case 'mailgun':
                $eventData = $payload['event-data'] ?? [];
                if (!empty($eventData)) {
                    $recipient = $eventData['recipient'] ?? null;
                    $severity = $eventData['severity'] ?? null;
                    if ($recipient) {
                        $bouncedEmails[] = $recipient;
                        $isHardBounce = ($severity === 'permanent');
                    }
                }
                break;

            case 'sendgrid':
                $events = is_array($payload) ? $payload : [$payload];
                foreach ($events as $event) {
                    $email = $event['email'] ?? null;
                    $type = $event['event'] ?? null;
                    if ($email && ($type === 'bounce' || $type === 'dropped')) {
                        $bouncedEmails[] = $email;
                        $isHardBounce = true;
                    }
                }
                break;

            case 'postmark':
                $email = $payload['Email'] ?? null;
                $type = $payload['Type'] ?? null;
                if ($email) {
                    $bouncedEmails[] = $email;
                    $isHardBounce = (strtolower($type) === 'hardbounce');
                }
                break;

            case 'ses':
            case 'aws':
                $notificationType = $payload['notificationType'] ?? null;
                if ($notificationType === 'Bounce') {
                    $bounce = $payload['bounce'] ?? [];
                    $bounceType = $bounce['bounceType'] ?? null; // Permanent or Transient
                    $recipients = $bounce['bouncedRecipients'] ?? [];
                    foreach ($recipients as $recipient) {
                        $email = $recipient['emailAddress'] ?? null;
                        if ($email) {
                            $bouncedEmails[] = $email;
                        }
                    }
                    $isHardBounce = ($bounceType === 'Permanent');
                }
                break;

            default:
                // Generic structure fallback
                $email = $payload['email'] ?? $payload['recipient'] ?? null;
                if ($email) {
                    $bouncedEmails[] = $email;
                }
                $isHardBounce = ($payload['type'] ?? $payload['severity'] ?? 'hard') === 'hard';
                break;
        }

        if (empty($bouncedEmails)) {
            return response()->json([
                'status' => 'ignored',
                'message' => 'No bounce data found in payload.'
            ]);
        }

        foreach ($bouncedEmails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                if ($isHardBounce) {
                    $user->update([
                        'email_bounced_at' => now(),
                        'email_bounce_type' => 'hard',
                    ]);

                    NotificationLog::create([
                        'user_id' => $user->id,
                        'channel' => 'email',
                        'type' => 'bounce_webhook',
                        'recipient' => $email,
                        'status' => 'failed',
                        'error_message' => 'Email hard bounced. Suspended outgoing mail.',
                    ]);

                    // Notify Admins
                    $admins = User::role(['Admin', 'Super Admin'])->get();
                    foreach ($admins as $admin) {
                        UserNotification::create([
                            'user_id' => $admin->id,
                            'type' => 'email_hard_bounce',
                            'title' => 'User Email Hard Bounced',
                            'body' => "Email address '{$email}' for user '{$user->name}' (ID: {$user->id}) has hard bounced.",
                        ]);
                    }
                } else {
                    $user->update([
                        'email_bounced_at' => now(),
                        'email_bounce_type' => 'soft',
                    ]);

                    NotificationLog::create([
                        'user_id' => $user->id,
                        'channel' => 'email',
                        'type' => 'bounce_webhook',
                        'recipient' => $email,
                        'status' => 'failed',
                        'error_message' => 'Email soft bounced.',
                    ]);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Bounce processed successfully.',
            'bounced_emails' => $bouncedEmails,
            'is_hard' => $isHardBounce,
        ]);
    }
}
