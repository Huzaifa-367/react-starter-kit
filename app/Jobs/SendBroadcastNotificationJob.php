<?php

namespace App\Jobs;

use App\Models\BroadcastNotification;
use App\Models\User;
use App\Models\FcmToken;
use App\Models\NotificationLog;
use App\Models\UserNotification;
use App\Services\FcmService;
use App\Services\GreenApiService;
use App\Services\TwilioService;
use App\Services\SegmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class SendBroadcastNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 300;

    protected BroadcastNotification $broadcast;

    /**
     * Create a new job instance.
     */
    public function __construct(BroadcastNotification $broadcast)
    {
        $this->broadcast = $broadcast;
        $this->queue = 'low';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $broadcast = $this->broadcast;
        $broadcast->update(['status' => 'sending']);

        $query = User::query();

        if ($broadcast->target_type === 'plan') {
            $query->whereHas('activeSubscription', function ($q) use ($broadcast) {
                $q->where('plan_id', $broadcast->target_id);
            });
        } elseif ($broadcast->target_type === 'role') {
            $role = Role::find($broadcast->target_id);
            if ($role) {
                $query->role($role->name);
            } else {
                $query->whereRaw('1=0');
            }
        } elseif ($broadcast->target_type === 'segment') {
            $segment = \App\Models\UserSegment::find($broadcast->target_id);
            if ($segment) {
                $segmentService = new SegmentService();
                $query = $segmentService->buildQuery($segment->filters ?? []);
            } else {
                $query->whereRaw('1=0');
            }
        }

        $channels = $broadcast->channels ?? [];
        $title = $broadcast->title;
        $body = $broadcast->body;

        $sentCount = 0;
        $failedCount = 0;

        $query->chunk(500, function ($users) use ($channels, $title, $body, $broadcast, &$sentCount, &$failedCount) {
            foreach ($users as $user) {
                $userSuccess = true;

                // 1. Email Channel
                if (in_array('email', $channels) && $user->email && $user->email_bounced_at === null) {
                    try {
                        Mail::html($body, function ($message) use ($user, $title) {
                            $message->to($user->email)->subject($title);
                        });
                        
                        NotificationLog::create([
                            'user_id' => $user->id,
                            'channel' => 'email',
                            'type' => 'broadcast_' . $broadcast->id,
                            'recipient' => $user->email,
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        $userSuccess = false;
                        NotificationLog::create([
                            'user_id' => $user->id,
                            'channel' => 'email',
                            'type' => 'broadcast_' . $broadcast->id,
                            'recipient' => $user->email,
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                        ]);
                    }
                }

                // 2. FCM Channel
                if (in_array('fcm', $channels) && FcmToken::where('user_id', $user->id)->active()->exists()) {
                    try {
                        $fcm = new FcmService();
                        $sent = $fcm->sendToUser($user, $title, $body, ['type' => 'broadcast', 'broadcast_id' => $broadcast->id]);
                        if (!$sent) {
                            $userSuccess = false;
                        }
                    } catch (\Exception $e) {
                        $userSuccess = false;
                    }
                }

                // 3. WhatsApp Channel
                if (in_array('whatsapp', $channels) && $user->phone_number) {
                    try {
                        $greenApi = new GreenApiService();
                        $sent = $greenApi->sendMessage($user->phone_number, $body);
                        if (!$sent) {
                            $userSuccess = false;
                        }
                    } catch (\Exception $e) {
                        $userSuccess = false;
                    }
                }

                // 4. SMS Channel
                if (in_array('sms', $channels) && $user->phone_number) {
                    try {
                        $twilio = new TwilioService();
                        $twilio->sendSms($user->phone_number, $body);
                    } catch (\Exception $e) {
                        $userSuccess = false;
                    }
                }

                // 5. In-App Notification
                try {
                    UserNotification::create([
                        'user_id' => $user->id,
                        'type' => 'broadcast',
                        'title' => $title,
                        'body' => $body,
                        'data' => ['broadcast_id' => $broadcast->id],
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to create in-app notification for broadcast: " . $e->getMessage());
                }

                if ($userSuccess) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }
        });

        $broadcast->update([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
        ]);
    }
}
