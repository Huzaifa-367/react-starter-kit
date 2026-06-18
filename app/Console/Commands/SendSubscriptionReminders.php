<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\BroadcastNotification;
use App\Models\User;
use App\Models\NotificationLog;
use App\Models\LoginHistory;
use App\Models\FcmToken;
use App\Models\SubscriptionUsage;
use App\Jobs\ProcessSubscriptionReminderJob;
use App\Jobs\DunningRetryJob;
use App\Jobs\SendBroadcastNotificationJob;
use App\Services\SegmentService;
use App\Services\SubscriptionManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendSubscriptionReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:send-subscription-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Execute daily SaaS cron tasks (reminders, dunning retries, expirations, cleanups)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting SaaS daily maintenance tasks...');

        $now = Carbon::now();

        // 1. Trial ending -> trialing AND trial_ends_at within 3 days
        Subscription::where('status', 'trialing')
            ->whereBetween('trial_ends_at', [$now, $now->copy()->addDays(3)])
            ->lazy(1000)
            ->each(function ($sub) {
                ProcessSubscriptionReminderJob::dispatch($sub, 'subscription_trial_end');
            });

        // 2. Renewal upcoming -> active, auto_renew=true, ends_at within 3 days
        Subscription::where('status', 'active')
            ->where('auto_renew', true)
            ->whereBetween('ends_at', [$now, $now->copy()->addDays(3)])
            ->lazy(1000)
            ->each(function ($sub) {
                ProcessSubscriptionReminderJob::dispatch($sub, 'subscription_renewal');
            });

        // 3. Grace expiring -> grace, grace_ends_at within 1 day
        Subscription::where('status', 'grace')
            ->whereBetween('grace_ends_at', [$now, $now->copy()->addDay()])
            ->lazy(1000)
            ->each(function ($sub) {
                ProcessSubscriptionReminderJob::dispatch($sub, 'subscription_grace');
            });

        // 4. Dunning retries -> status=grace AND next_retry_at <= now()
        Subscription::where('status', 'grace')
            ->where('next_retry_at', '<=', $now)
            ->lazy(1000)
            ->each(function ($sub) {
                DunningRetryJob::dispatch($sub);
            });

        // 5. Expire overdue -> status IN (canceled,grace) AND ends_at < now()
        $subManager = new SubscriptionManager();
        Subscription::whereIn('status', ['canceled', 'grace'])
            ->where('ends_at', '<', $now)
            ->lazy(1000)
            ->each(function ($sub) use ($subManager) {
                $sub->update(['status' => 'expired']);
                $subManager->subscribeTo($sub->user, \App\Models\Plan::where('price', 0)->first() ?: \App\Models\Plan::first());
                \App\Services\NotificationDispatcher::dispatch($sub->user, 'subscription_expired');
            });

        // 6. Scheduled broadcasts -> status=scheduled AND scheduled_at <= now()
        BroadcastNotification::where('status', 'scheduled')
            ->where('scheduled_at', '<=', $now)
            ->lazy(1000)
            ->each(function ($broadcast) {
                SendBroadcastNotificationJob::dispatch($broadcast);
            });

        // 7. Refresh segment counts
        try {
            $segmentService = new SegmentService();
            $segmentService->refreshAllCounts();
        } catch (\Exception $e) {
            Log::error("Failed to refresh segment counts: " . $e->getMessage());
        }

        // 8. Reset feature usages -> reset_at < now()
        SubscriptionUsage::where('reset_at', '<', $now)
            ->lazy(1000)
            ->each(function ($usage) {
                $feature = \App\Models\Feature::find($usage->feature_id);
                $resetAt = null;
                if ($feature) {
                    if ($feature->resettable_period === 'daily') {
                        $resetAt = Carbon::now()->addDay();
                    } elseif ($feature->resettable_period === 'weekly') {
                        $resetAt = Carbon::now()->addWeek();
                    } elseif ($feature->resettable_period === 'monthly') {
                        $resetAt = Carbon::now()->addMonth();
                    } elseif ($feature->resettable_period === 'yearly') {
                        $resetAt = Carbon::now()->addYear();
                    }
                }
                $usage->update([
                    'used' => 0,
                    'reset_at' => $resetAt,
                ]);
            });

        // 9. Cleanup OTP codes -> otp_expires_at < now()-1hr
        User::where('otp_expires_at', '<', $now->copy()->subHour())
            ->lazy(1000)
            ->each(function ($user) {
                $user->update([
                    'otp_code' => null,
                    'otp_expires_at' => null,
                    'otp_purpose' => null,
                ]);
            });

        // 10. Cleanup notification logs -> created_at < now()-30days
        NotificationLog::where('created_at', '<', $now->copy()->subDays(30))->delete();

        // 11. Cleanup login history -> login_at < now()-90days
        LoginHistory::where('login_at', '<', $now->copy()->subDays(90))->delete();

        // 12. Deactivate stale FCM tokens -> last_used_at < now()-30days
        FcmToken::where('last_used_at', '<', $now->copy()->subDays(30))
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // 13. Hard-delete soft-deleted users -> deleted_at < now()-30days
        User::onlyTrashed()
            ->where('deleted_at', '<', $now->copy()->subDays(30))
            ->lazy(1000)
            ->each(function ($user) {
                $stripeSecret = \App\Models\Setting::get('stripe_secret') ?: env('STRIPE_SECRET');
                if ($user->stripe_id && !empty($stripeSecret)) {
                    try {
                        \Stripe\Stripe::setApiKey($stripeSecret);
                        \Stripe\Customer::retrieve($user->stripe_id)->delete();
                    } catch (\Exception $e) {
                        Log::error("Failed to delete Stripe customer {$user->stripe_id} for soft deleted user {$user->id}: " . $e->getMessage());
                    }
                }
                $user->forceDelete();
            });

        $this->info('SaaS daily maintenance tasks completed successfully.');
    }
}
