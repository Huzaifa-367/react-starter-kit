<?php

namespace App\Services;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use App\Models\Feature;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class SubscriptionManager
{
    /**
     * Subscribe a user to a plan.
     */
    public function subscribeTo(User $user, Plan $plan, ?string $stripeSubId = null): Subscription
    {
        // 1. If existing non-expired subscription exists -> cancelImmediately first
        $existing = $user->subscriptions()
            ->where('status', '!=', 'expired')
            ->first();
        if ($existing) {
            $this->cancelImmediately($existing);
        }

        // Clean up any lingering usages for this user to satisfy feature_usages_unique constraint
        SubscriptionUsage::where('subscribable_type', User::class)
            ->where('subscribable_id', $user->id)
            ->delete();

        // 2. Compute ends_at timeline and status
        $trialDays = $plan->trial_days ?? 0;
        if ($trialDays > 0) {
            $trialEndsAt = Carbon::now()->addDays($trialDays);
            $status = 'trialing';
            $billingStartsAt = $trialEndsAt;
            $endsAt = $trialEndsAt;
        } else {
            $trialEndsAt = null;
            $status = 'active';
            $billingStartsAt = Carbon::now();
            if ($plan->billing_period === 'monthly') {
                $endsAt = Carbon::now()->addMonth();
            } elseif ($plan->billing_period === 'yearly') {
                $endsAt = Carbon::now()->addYear();
            } else {
                $endsAt = null; // lifetime
            }
        }

        // 3. Create subscription row
        $sub = Subscription::create([
            'user_id' => $user->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $user->id,
            'plan_id' => $plan->id,
            'name' => 'default',
            'status' => $status,
            'stripe_id' => $stripeSubId,
            'stripe_status' => $stripeSubId ? 'active' : null,
            'trial_ends_at' => $trialEndsAt,
            'billing_starts_at' => $billingStartsAt,
            'ends_at' => $endsAt,
            'auto_renew' => true,
        ]);

        // 4. Seed one subscription_usages row per plan feature with used = 0
        foreach ($plan->features as $feature) {
            $resetAt = null;
            if ($feature->resettable_period === 'daily') {
                $resetAt = Carbon::now()->addDay();
            } elseif ($feature->resettable_period === 'weekly') {
                $resetAt = Carbon::now()->addWeek();
            } elseif ($feature->resettable_period === 'monthly') {
                $resetAt = Carbon::now()->addMonth();
            } elseif ($feature->resettable_period === 'yearly') {
                $resetAt = Carbon::now()->addYear();
            }

            SubscriptionUsage::create([
                'subscription_id' => $sub->id,
                'subscribable_type' => User::class,
                'subscribable_id' => $user->id,
                'feature_id' => $feature->id,
                'feature_slug' => $feature->slug,
                'used' => 0,
                'overage' => 0,
                'reset_at' => $resetAt,
            ]);
        }

        // 5. Assign Spatie role: User (Subscribed) if paid, User (Free) if free
        if ($plan->isFree()) {
            $user->assignRole('User (Free)');
            $user->removeRole('User (Subscribed)');
        } else {
            $user->assignRole('User (Subscribed)');
            $user->removeRole('User (Free)');
        }

        // 6. Flush cache keys
        $user->flushSubscriptionCache();

        // 7. Dispatch notification
        if (class_exists(\App\Services\NotificationDispatcher::class)) {
            \App\Services\NotificationDispatcher::dispatch($user, 'subscription_activated');
        }

        return $sub;
    }

    /**
     * Change user subscription plan with optional Stripe proration.
     */
    public function changePlan(User $user, Plan $newPlan): Subscription
    {
        $sub = $user->getActiveSubscription();
        if (!$sub) {
            return $this->subscribeTo($user, $newPlan);
        }

        if ($newPlan->isFree()) {
            if ($sub->stripe_id) {
                $this->cancelImmediately($sub);
            }
            return $this->subscribeTo($user, $newPlan);
        }

        if (!$sub->stripe_id && !$newPlan->isFree()) {
            throw new \Exception("Cannot upgrade from a free plan to a paid plan without payment. Please subscribe via the Checkout portal.");
        }

        $previousPlanId = $sub->plan_id;

        // Sync with Stripe if stripe_id and credentials exist
        $stripeSecret = Setting::get('stripe_secret');
        if ($sub->stripe_id && !empty($stripeSecret)) {
            try {
                \Stripe\Stripe::setApiKey($stripeSecret);
                $stripeSub = \Stripe\Subscription::retrieve($sub->stripe_id);

                $priceId = $newPlan->stripe_price_id;

                if ($priceId && !empty($stripeSub->items->data)) {
                    $subItemId = $stripeSub->items->data[0]->id;
                    \Stripe\Subscription::update($sub->stripe_id, [
                        'items' => [
                            [
                                'id' => $subItemId,
                                'price' => $priceId,
                            ],
                        ],
                        'proration_behavior' => 'create_prorations',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Stripe plan change failed during changePlan(): " . $e->getMessage());
                throw $e;
            }
        }

        // Update local database row
        $trialDays = $newPlan->trial_days ?? 0;
        if ($trialDays > 0) {
            $trialEndsAt = Carbon::now()->addDays($trialDays);
            $status = 'trialing';
            $endsAt = $trialEndsAt;
        } else {
            $trialEndsAt = null;
            $status = 'active';
            if ($newPlan->billing_period === 'monthly') {
                $endsAt = Carbon::now()->addMonth();
            } elseif ($newPlan->billing_period === 'yearly') {
                $endsAt = Carbon::now()->addYear();
            } else {
                $endsAt = null; // lifetime
            }
        }

        $sub->update([
            'plan_id' => $newPlan->id,
            'previous_plan_id' => $previousPlanId,
            'status' => $status,
            'trial_ends_at' => $trialEndsAt,
            'ends_at' => $endsAt,
        ]);

        // Seed any missing subscription_usages for new plan's features
        foreach ($newPlan->features as $feature) {
            $usage = $sub->usages()->where('feature_id', $feature->id)->first();
            if (!$usage) {
                $resetAt = null;
                if ($feature->resettable_period === 'daily') {
                    $resetAt = Carbon::now()->addDay();
                } elseif ($feature->resettable_period === 'weekly') {
                    $resetAt = Carbon::now()->addWeek();
                } elseif ($feature->resettable_period === 'monthly') {
                    $resetAt = Carbon::now()->addMonth();
                } elseif ($feature->resettable_period === 'yearly') {
                    $resetAt = Carbon::now()->addYear();
                }

                SubscriptionUsage::create([
                    'subscription_id' => $sub->id,
                    'subscribable_type' => User::class,
                    'subscribable_id' => $user->id,
                    'feature_id' => $feature->id,
                    'feature_slug' => $feature->slug,
                    'used' => 0,
                    'overage' => 0,
                    'reset_at' => $resetAt,
                ]);
            }
        }

        // Assign Spatie role: User (Subscribed) if paid, User (Free) if free
        if ($newPlan->isFree()) {
            $user->assignRole('User (Free)');
            $user->removeRole('User (Subscribed)');
        } else {
            $user->assignRole('User (Subscribed)');
            $user->removeRole('User (Free)');
        }

        $user->flushSubscriptionCache();

        if (class_exists(\App\Services\NotificationDispatcher::class)) {
            \App\Services\NotificationDispatcher::dispatch($user, 'subscription_activated');
        }

        return $sub;
    }

    /**
     * Cancel a subscription at the end of the billing period.
     */
    public function cancelAtPeriodEnd(User $user): Subscription
    {
        $sub = $user->getActiveSubscription();
        if (!$sub) {
            throw new \Exception("No active subscription to cancel.");
        }

        // Cancel in Stripe if stripe_id and credentials exist
        $stripeSecret = Setting::get('stripe_secret');
        if ($sub->stripe_id && !empty($stripeSecret)) {
            try {
                \Stripe\Stripe::setApiKey($stripeSecret);
                \Stripe\Subscription::update($sub->stripe_id, [
                    'cancel_at_period_end' => true,
                ]);
            } catch (\Exception $e) {
                Log::error("Stripe cancel at period end failed: " . $e->getMessage());
            }
        }

        $sub->update([
            'auto_renew' => false,
            'status' => 'canceled',
            'canceled_at' => Carbon::now(),
        ]);

        $user->flushSubscriptionCache();

        if (class_exists(\App\Services\NotificationDispatcher::class)) {
            \App\Services\NotificationDispatcher::dispatch($user, 'subscription_canceled');
        }

        return $sub;
    }

    /**
     * Cancel subscription immediately.
     */
    public function cancelImmediately(Subscription $sub): void
    {
        // Cancel in Stripe if stripe_id and credentials exist
        $stripeSecret = Setting::get('stripe_secret');
        if ($sub->stripe_id && !empty($stripeSecret)) {
            try {
                \Stripe\Stripe::setApiKey($stripeSecret);
                $stripeSub = \Stripe\Subscription::retrieve($sub->stripe_id);
                $stripeSub->cancel();
            } catch (\Exception $e) {
                Log::error("Stripe immediate cancel failed: " . $e->getMessage());
            }
        }

        $sub->update([
            'status' => 'expired',
            'ends_at' => Carbon::now(),
        ]);

        $sub->usages()->delete();

        $sub->user->flushSubscriptionCache();

        if (class_exists(\App\Services\NotificationDispatcher::class)) {
            \App\Services\NotificationDispatcher::dispatch($sub->user, 'subscription_expired');
        }
    }

    /**
     * Set subscription status to grace and store failure timeline in metadata.
     */
    public function enterGracePeriod(Subscription $sub): void
    {
        $metadata = $sub->metadata ?? [];
        $metadata['payment_failed_at'] = Carbon::now()->toIso8601String();

        $sub->update([
            'status' => 'grace',
            'grace_ends_at' => Carbon::now()->addDays($sub->plan->grace_days ?? 7),
            'payment_failed_at' => Carbon::now(),
            'metadata' => $metadata,
        ]);

        $sub->user->flushSubscriptionCache();

        if (class_exists(\App\Services\NotificationDispatcher::class)) {
            \App\Services\NotificationDispatcher::dispatch($sub->user, 'subscription_grace');
        }
    }

    /**
     * Handle successful renewal payment or payment recovery.
     */
    public function handleRenewalSucceeded(Subscription $sub): void
    {
        $metadata = $sub->metadata ?? [];
        unset($metadata['payment_failed_at']);
        unset($metadata['next_retry_at']);

        $sub->update([
            'status' => 'active',
            'payment_failed_at' => null,
            'grace_ends_at' => null,
            'metadata' => $metadata,
        ]);

        foreach ($sub->usages as $usage) {
            $feature = Feature::find($usage->feature_id);
            if (!$feature) {
                continue;
            }

            $nextResetAt = null;
            if ($feature->resettable_period === 'daily') {
                $nextResetAt = Carbon::now()->addDay();
            } elseif ($feature->resettable_period === 'weekly') {
                $nextResetAt = Carbon::now()->addWeek();
            } elseif ($feature->resettable_period === 'monthly') {
                $nextResetAt = Carbon::now()->addMonth();
            } elseif ($feature->resettable_period === 'yearly') {
                $nextResetAt = Carbon::now()->addYear();
            }

            $usage->update([
                'used' => 0,
                'overage' => 0,
                'last_reset_at' => Carbon::now(),
                'reset_at' => $nextResetAt,
            ]);
        }

        $sub->user->flushSubscriptionCache();
    }

    /**
     * Resume a canceled subscription before the period ends.
     */
    public function resume(User $user): Subscription
    {
        $sub = $user->getActiveSubscription();
        if (!$sub) {
            throw new \Exception("No subscription to resume.");
        }

        // Resume in Stripe if stripe_id and credentials exist
        $stripeSecret = Setting::get('stripe_secret');
        if ($sub->stripe_id && !empty($stripeSecret)) {
            try {
                \Stripe\Stripe::setApiKey($stripeSecret);
                \Stripe\Subscription::update($sub->stripe_id, [
                    'cancel_at_period_end' => false,
                ]);
            } catch (\Exception $e) {
                Log::error("Stripe subscription resume failed: " . $e->getMessage());
            }
        }

        $sub->update([
            'auto_renew' => true,
            'status' => 'active',
            'canceled_at' => null,
        ]);

        $user->flushSubscriptionCache();

        return $sub;
    }

    /**
     * Downgrade user subscription to a Free tier.
     */
    public function downgradeToFree(User $user): Subscription
    {
        $freePlan = Plan::where('price', 0.00)->first();
        if (!$freePlan) {
            throw new \Exception("Free plan not configured in database.");
        }

        $sub = $user->getActiveSubscription();
        if ($sub) {
            $this->cancelImmediately($sub);
        }

        $newSub = $this->subscribeTo($user, $freePlan);

        $user->removeRole('User (Subscribed)');
        $user->assignRole('User (Free)');
        $user->flushSubscriptionCache();

        return $newSub;
    }

    /**
     * Reset feature usage for features whose reset_at date has passed.
     */
    public function resetFeatureUsages(Subscription $sub): void
    {
        $usages = $sub->usages()->where('reset_at', '<', Carbon::now())->get();
        
        foreach ($usages as $usage) {
            $feature = Feature::find($usage->feature_id);
            if (!$feature) {
                continue;
            }

            $nextResetAt = null;
            if ($feature->resettable_period === 'daily') {
                $nextResetAt = Carbon::now()->addDay();
            } elseif ($feature->resettable_period === 'weekly') {
                $nextResetAt = Carbon::now()->addWeek();
            } elseif ($feature->resettable_period === 'monthly') {
                $nextResetAt = Carbon::now()->addMonth();
            } elseif ($feature->resettable_period === 'yearly') {
                $nextResetAt = Carbon::now()->addYear();
            }

            $usage->update([
                'used' => 0,
                'overage' => 0,
                'last_reset_at' => Carbon::now(),
                'reset_at' => $nextResetAt,
            ]);
        }

        $sub->user->flushSubscriptionCache();
    }

    /**
     * Sync local subscription details with Stripe webhooks or state retrieval.
     */
    public function syncFromStripe(object $stripeSubscription): void
    {
        $sub = Subscription::where('stripe_id', $stripeSubscription->id)->first();
        if (!$sub) {
            return;
        }

        $statusMap = [
            'trialing' => 'trialing',
            'active' => 'active',
            'past_due' => 'grace',
            'canceled' => 'canceled',
            'unpaid' => 'expired',
            'incomplete' => 'grace',
            'incomplete_expired' => 'expired',
        ];

        $stripeStatus = $stripeSubscription->status;
        $localStatus = $statusMap[$stripeStatus] ?? 'active';

        $sub->update([
            'stripe_status' => $stripeStatus,
            'status' => $localStatus,
            'ends_at' => $stripeSubscription->current_period_end ? Carbon::createFromTimestamp($stripeSubscription->current_period_end) : null,
            'trial_ends_at' => $stripeSubscription->trial_end ? Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
            'auto_renew' => !$stripeSubscription->cancel_at_period_end,
            'canceled_at' => $stripeSubscription->canceled_at ? Carbon::createFromTimestamp($stripeSubscription->canceled_at) : null,
        ]);

        // If the price ID has changed, find the plan corresponding to that price and update plan_id
        if (!empty($stripeSubscription->items->data)) {
            $stripePriceId = $stripeSubscription->items->data[0]->price->id;
            $newPlan = Plan::where('stripe_price_id', $stripePriceId)
                ->first();
            
            if ($newPlan && $sub->plan_id !== $newPlan->id) {
                $sub->update([
                    'previous_plan_id' => $sub->plan_id,
                    'plan_id' => $newPlan->id,
                ]);
            }
        }

        $sub->user->flushSubscriptionCache();
    }

    /**
     * Schedule payment dunning retries by computing delay offsets.
     */
    public function scheduleDunningRetry(Subscription $sub, int $dayOffset): void
    {
        $metadata = $sub->metadata ?? [];
        
        $paymentFailedAtStr = $metadata['payment_failed_at'] ?? Carbon::now()->toIso8601String();
        $paymentFailedAt = Carbon::parse($paymentFailedAtStr);
        
        $metadata['next_retry_at'] = $paymentFailedAt->addDays($dayOffset)->toIso8601String();

        $sub->update([
            'metadata' => $metadata,
        ]);
    }
}
