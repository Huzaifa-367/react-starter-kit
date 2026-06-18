<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\Setting;
use App\Services\NotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class DunningRetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 60;

    protected Subscription $subscription;

    /**
     * Create a new job instance.
     */
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sub = $this->subscription;
        if (!$sub->stripe_id) {
            return;
        }

        $stripeSecret = Setting::get('stripe_secret') ?: env('STRIPE_SECRET');
        if (empty($stripeSecret)) {
            Log::error("Stripe secret key missing. Cannot execute DunningRetryJob.");
            return;
        }

        \Stripe\Stripe::setApiKey($stripeSecret);

        try {
            // Retrieve subscription open invoices
            $invoices = \Stripe\Invoice::all([
                'subscription' => $sub->stripe_id,
                'status' => 'open',
                'limit' => 1,
            ]);

            if (empty($invoices->data)) {
                Log::info("No open invoices found for Stripe subscription {$sub->stripe_id}.");
                return;
            }

            $invoice = $invoices->data[0];
            $paidInvoice = $invoice->pay();

            if ($paidInvoice->status === 'paid') {
                $sub->update([
                    'status' => 'active',
                    'payment_failed_at' => null,
                    'retry_count' => 0,
                    'next_retry_at' => null,
                ]);

                NotificationDispatcher::dispatch($sub->user, 'subscription_renewed');
            }
        } catch (\Exception $e) {
            Log::warning("Dunning payment retry failed for subscription {$sub->id}: " . $e->getMessage());

            $retryCount = $sub->retry_count + 1;
            $nextRetryAt = null;

            if ($retryCount === 1) {
                // Retry after 2 days (Day 3 since first fail)
                $nextRetryAt = Carbon::now()->addDays(2);
            } elseif ($retryCount === 2) {
                // Retry after 4 days (Day 7 since first fail)
                $nextRetryAt = Carbon::now()->addDays(4);
            } else {
                // Exceeded retries, cancel subscription and downgrade to Free
                $sub->update([
                    'status' => 'expired',
                    'ends_at' => Carbon::now(),
                    'next_retry_at' => null,
                ]);

                if (class_exists(\App\Services\SubscriptionManager::class)) {
                    $subManager = new \App\Services\SubscriptionManager();
                    $subManager->subscribeTo($sub->user, \App\Models\Plan::where('price', 0)->first() ?: \App\Models\Plan::first());
                }

                NotificationDispatcher::dispatch($sub->user, 'subscription_expired');
                return;
            }

            $sub->update([
                'retry_count' => $retryCount,
                'next_retry_at' => $nextRetryAt,
            ]);

            NotificationDispatcher::dispatch($sub->user, 'dunning_retry');
        }
    }
}
