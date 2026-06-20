<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Models\Setting;
use App\Services\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\BillingPortal\Session as StripePortalSession;
use Stripe\Subscription as StripeSubscription;
use Stripe\Invoice as StripeInvoice;
use Stripe\Webhook as StripeWebhook;
use Stripe\Event as StripeEvent;
use Carbon\Carbon;

class StripeBillingController extends Controller
{
    /**
     * Create a new Stripe Checkout Session.
     */
    public function checkoutSession(Request $request)
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['nullable', 'in:monthly,yearly'],
            'coupon' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $plan = Plan::findOrFail($request->plan_id);

        // If Free plan, bypass Stripe and subscribe directly
        if ($plan->isFree()) {
            $subManager = new SubscriptionManager();
            $subManager->subscribeTo($user, $plan);

            if ($request->wantsJson()) {
                return response()->json(['redirect' => '/dashboard']);
            }
            return redirect()->route('dashboard')->with('status', 'Successfully subscribed to the Free plan!');
        }

        // Get Stripe secret key
        $stripeSecret = Setting::get('stripe_secret') ?: env('STRIPE_SECRET');
        if (!$stripeSecret) {
            abort(500, 'Stripe is not configured.');
        }

        Stripe::setApiKey($stripeSecret);

        // Get or create Stripe Customer
        if (!$user->stripe_id) {
            try {
                $customer = \Stripe\Customer::create([
                    'email' => $user->email,
                    'name' => $user->name,
                ]);
                $user->stripe_id = $customer->id;
                $user->save();
            } catch (\Exception $e) {
                Log::error("Failed to create Stripe customer: " . $e->getMessage());
                return response()->json(['error' => 'Stripe connection error.'], 500);
            }
        }

        // Determine correct Price ID
        $priceId = $plan->stripe_price_id;
        $billingCycle = $request->input('billing_cycle') ?: ($plan->billing_period === 'yearly' ? 'yearly' : 'monthly');

        if (!$priceId) {
            return response()->json(['error' => 'Selected plan does not have a Stripe Price configured.'], 422);
        }

        $isLifetime = $plan->billing_period === 'lifetime';

        // Build Stripe Checkout Session
        $sessionParams = [
            'customer' => $user->stripe_id,
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'mode' => $isLifetime ? 'payment' : 'subscription',
            'success_url' => route('billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('pricing'),
            'allow_promotion_codes' => true,
            'metadata' => [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
            ],
        ];

        // Apply coupon if explicitly set
        if ($request->coupon) {
            $sessionParams['discounts'] = [['coupon' => $request->coupon]];
        }

        // Attach trial days if configured on plan (only applicable to subscription mode)
        if ($plan->trial_days > 0 && !$isLifetime) {
            $sessionParams['subscription_data'] = [
                'trial_period_days' => $plan->trial_days,
            ];
        }

        try {
            $session = StripeCheckoutSession::create($sessionParams);
            return response()->json(['checkout_url' => $session->url]);
        } catch (\Exception $e) {
            Log::error("Failed to create Stripe checkout session: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Preview proration details when upgrading/downgrading.
     */
    public function previewProration(Request $request)
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['nullable', 'in:monthly,yearly'],
        ]);

        $user = $request->user();
        $sub = $user->getActiveSubscription();

        if (!$user->stripe_id || !$sub || !$sub->stripe_id) {
            return response()->json([
                'credit_applied' => '$0.00',
                'new_charge' => '$0.00',
                'total_due_today' => '$0.00',
                'next_billing_date' => 'N/A',
            ]);
        }

        $newPlan = Plan::findOrFail($request->plan_id);
        $newPriceId = $newPlan->stripe_price_id;

        $stripeSecret = Setting::get('stripe_secret') ?: env('STRIPE_SECRET');
        if (!$stripeSecret || !$newPriceId) {
            return response()->json([
                'credit_applied' => '$0.00',
                'new_charge' => '$0.00',
                'total_due_today' => '$0.00',
                'next_billing_date' => 'N/A',
            ]);
        }

        try {
            Stripe::setApiKey($stripeSecret);
            $stripeSub = StripeSubscription::retrieve($sub->stripe_id);
            $subItemId = $stripeSub->items->data[0]->id;

            $upcoming = StripeInvoice::upcoming([
                'customer' => $user->stripe_id,
                'subscription' => $sub->stripe_id,
                'subscription_items' => [[
                    'id' => $subItemId,
                    'price' => $newPriceId,
                ]],
            ]);

            $totalDueToday = $upcoming->amount_due / 100;
            $newCharge = $upcoming->subtotal / 100;
            $creditApplied = ($upcoming->subtotal - $upcoming->amount_due) / 100;

            return response()->json([
                'credit_applied' => '$' . number_format(max(0, $creditApplied), 2),
                'new_charge' => '$' . number_format($newCharge, 2),
                'total_due_today' => '$' . number_format($totalDueToday, 2),
                'next_billing_date' => Carbon::createFromTimestamp($upcoming->next_payment_attempt ?? time())->toDateString(),
            ]);

        } catch (\Exception $e) {
            Log::error("Stripe proration preview failed: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Generate customer billing portal link.
     */
    public function billingPortal(Request $request)
    {
        $user = $request->user();

        if (!$user->stripe_id) {
            return response()->json(['error' => 'No billing history found.'], 400);
        }

        $stripeSecret = Setting::get('stripe_secret') ?: env('STRIPE_SECRET');
        if (!$stripeSecret) {
            return response()->json(['error' => 'Stripe is not configured.'], 500);
        }

        try {
            Stripe::setApiKey($stripeSecret);
            $session = StripePortalSession::create([
                'customer' => $user->stripe_id,
                'return_url' => route('billing.dashboard'),
            ]);

            return response()->json(['portal_url' => $session->url]);
        } catch (\Exception $e) {
            Log::error("Stripe billing portal creation failed: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Confirm a successful checkout redirect.
     */
    public function checkoutSuccess(Request $request)
    {
        $sessionId = $request->query('session_id');

        if ($sessionId) {
            try {
                $stripeSecret = Setting::get('stripe_secret') ?: env('STRIPE_SECRET');
                Stripe::setApiKey($stripeSecret);

                $session = StripeCheckoutSession::retrieve($sessionId);
                $userId = $session->metadata->user_id ?? null;
                $planId = $session->metadata->plan_id ?? null;

                if ($userId && $planId) {
                    $user = User::find($userId);
                    $plan = Plan::find($planId);
                    if ($user && $plan) {
                        $subManager = new SubscriptionManager();
                        $stripeId = $session->subscription ?? $session->payment_intent ?? $session->id;
                        $exists = \App\Models\Subscription::where('stripe_id', $stripeId)->exists();
                        if (!$exists) {
                            $subManager->subscribeTo($user, $plan, $stripeId);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Checkout success retrieval failed: " . $e->getMessage());
            }
        }

        return redirect()->route('dashboard')->with('status', 'Your subscription is now active!');
    }

    /**
     * Cancel the active subscription at period end.
     */
    public function cancelSubscription(Request $request): RedirectResponse
    {
        try {
            $subManager = new SubscriptionManager();
            $subManager->cancelAtPeriodEnd($request->user());
            return back()->with('status', 'Your subscription has been scheduled to cancel at the end of your billing cycle.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Resume a canceled subscription before period ends.
     */
    public function resumeSubscription(Request $request): RedirectResponse
    {
        try {
            $subManager = new SubscriptionManager();
            $subManager->resume($request->user());
            return back()->with('status', 'Your subscription auto-renewal has been successfully resumed.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Change user subscription plan (upgrade/downgrade).
     */
    public function changePlan(Request $request): RedirectResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        try {
            $user = $request->user();
            $plan = Plan::findOrFail($request->plan_id);

            $subManager = new SubscriptionManager();
            $subManager->changePlan($user, $plan);

            return back()->with('status', 'Your subscription has been upgraded/downgraded successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle incoming Stripe webhooks.
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = Setting::get('stripe_webhook_secret') ?: env('STRIPE_WEBHOOK_SECRET');

        try {
            $stripeSecret = Setting::get('stripe_secret') ?: env('STRIPE_SECRET');
            Stripe::setApiKey($stripeSecret);

            if ($endpointSecret && $sigHeader) {
                $event = StripeWebhook::constructEvent($payload, $sigHeader, $endpointSecret);
            } else {
                $event = StripeEvent::constructFrom(json_decode($payload, true));
            }
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $subManager = new SubscriptionManager();

        // Idempotency check: find or create webhook log
        $webhookLog = \App\Models\WebhookLog::firstOrCreate([
            'event_id' => $event->id,
        ], [
            'source' => 'stripe',
            'event_type' => $event->type,
            'payload' => json_decode($payload, true) ?: [],
            'processed' => false,
        ]);

        if ($webhookLog->processed) {
            return response()->json(['status' => 'success', 'message' => 'Event already processed']);
        }

        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    $session = $event->data->object;
                    $userId = $session->metadata->user_id ?? null;
                    $planId = $session->metadata->plan_id ?? null;

                    if ($userId && $planId) {
                        $user = User::find($userId);
                        $plan = Plan::find($planId);
                        if ($user && $plan) {
                            $stripeId = $session->subscription ?? $session->payment_intent ?? $session->id;
                            $subManager->subscribeTo($user, $plan, $stripeId);
                        }
                    }
                    break;

                case 'customer.subscription.updated':
                case 'customer.subscription.deleted':
                    $stripeSubscription = $event->data->object;
                    $subManager->syncFromStripe($stripeSubscription);
                    break;

                case 'invoice.payment_succeeded':
                    $invoice = $event->data->object;
                    // Reset usages and restore status on recurring renewals or past-due manual invoice payments
                    $stripeSubId = $invoice->subscription;
                    if ($stripeSubId) {
                        $sub = \App\Models\Subscription::where('stripe_id', $stripeSubId)->first();
                        if ($sub) {
                            $subManager->handleRenewalSucceeded($sub);
                        }
                    }
                    break;

                case 'invoice.payment_failed':
                    $invoice = $event->data->object;
                    $stripeSubId = $invoice->subscription;
                    if ($stripeSubId) {
                        $sub = \App\Models\Subscription::where('stripe_id', $stripeSubId)->first();
                        if ($sub) {
                            $subManager->enterGracePeriod($sub);
                        }
                    }
                    break;
            }

            $webhookLog->update([
                'processed' => true,
                'error' => null,
            ]);
        } catch (\Exception $e) {
            $webhookLog->update([
                'processed' => false,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return response()->json(['status' => 'success']);
    }
}
