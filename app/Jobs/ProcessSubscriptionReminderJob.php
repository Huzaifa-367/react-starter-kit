<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\NotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSubscriptionReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 30;

    protected Subscription $subscription;
    protected string $event;

    /**
     * Create a new job instance.
     */
    public function __construct(Subscription $subscription, string $event)
    {
        $this->subscription = $subscription;
        $this->event = $event;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = $this->subscription->user;
        if (!$user) {
            return;
        }

        NotificationDispatcher::dispatch($user, $this->event, [
            'subscription' => $this->subscription,
            'plan_name' => $this->subscription->plan?->name,
        ]);
    }
}
