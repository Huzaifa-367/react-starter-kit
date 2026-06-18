<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserCredit;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class CreditService
{
    /**
     * Get the sum of non-expired, unused credits.
     */
    public function getBalance(User $user): float
    {
        return (float) UserCredit::where('user_id', $user->id)
            ->available()
            ->sum('amount');
    }

    /**
     * Grant credit to a user.
     */
    public function grant(User $user, float $amount, string $type, string $description): void
    {
        $expiryDays = (int) Setting::get('credit_expiry_days', 90);

        UserCredit::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'expires_at' => now()->addDays($expiryDays),
        ]);
    }

    /**
     * Apply user credits to Stripe as customer balance and mark as used locally.
     */
    public function apply(User $user, string $checkoutSessionId): void
    {
        $balance = $this->getBalance($user);
        if ($balance <= 0 || empty($user->stripe_id)) {
            return;
        }

        $stripeSecret = Setting::get('stripe_secret');
        if (empty($stripeSecret)) {
            return;
        }

        try {
            \Stripe\Stripe::setApiKey($stripeSecret);

            // Stripe customer balance: negative means the customer has credit, positive means they owe money.
            // Adjust customer balance using Stripe Customer Balance Transaction API
            $amountCents = -1 * (int) round($balance * 100);
            $currency = strtolower(Setting::get('app_currency', 'usd'));

            \Stripe\Customer::createBalanceTransaction($user->stripe_id, [
                'amount' => $amountCents,
                'currency' => $currency,
                'description' => "Applied available credits from session {$checkoutSessionId}",
            ]);

            // Mark local credits as used
            UserCredit::where('user_id', $user->id)
                ->available()
                ->update([
                    'used_at' => now(),
                ]);

        } catch (\Exception $e) {
            Log::error("Stripe credit application failed in CreditService: " . $e->getMessage());
        }
    }

    /**
     * Check if user has any available credits.
     */
    public function hasCredits(User $user): bool
    {
        return $this->getBalance($user) > 0;
    }
}
