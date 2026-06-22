<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Stripe;
use Stripe\Invoice as StripeInvoice;
use Carbon\Carbon;

class PlanController extends Controller
{
    /**
     * Display the pricing page.
     */
    public function pricing(Request $request): Response
    {
        $plans = Plan::active()->ordered()->with('features')->get();
        $activeSubscription = null;
        
        if (Auth::check()) {
            $activeSubscription = Auth::user()->getActiveSubscription();
        }

        return Inertia::render('billing/pricing', [
            'plans' => $plans,
            'activeSubscription' => $activeSubscription,
        ]);
    }

    /**
     * Display the billing dashboard.
     */
    public function dashboard(Request $request): \Inertia\Response|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $sub = $user->getActiveSubscription();

        if (!$sub) {
            return redirect()->route('pricing');
        }

        // Calculate usage percentages for features
        $usages = [];
        if ($sub->plan) {
            foreach ($sub->plan->features as $feature) {
                $limit = $feature->pivot->value; // 'unlimited' or an integer
                $used = $user->getFeatureUsage($feature->slug);

                $percentage = 0;
                if (is_numeric($limit) && (int)$limit > 0) {
                    $percentage = min(100, (int)(($used / (int)$limit) * 100));
                }

                $usages[] = [
                    'feature_name' => $feature->name,
                    'feature_slug' => $feature->slug,
                    'used' => $used,
                    'limit' => $limit,
                    'percentage' => $percentage,
                ];
            }
        }

        // Retrieve last 12 invoices from Stripe
        $invoices = [];
        if ($user->stripe_id) {
            try {
                $stripeSecret = config('services.stripe.secret');
                if ($stripeSecret) {
                    Stripe::setApiKey($stripeSecret);
                    $stripeInvoices = StripeInvoice::all([
                        'customer' => $user->stripe_id,
                        'limit' => 12,
                    ]);

                    foreach ($stripeInvoices->data as $inv) {
                        $invoices[] = [
                            'id' => $inv->id,
                            'number' => $inv->number,
                            'amount_paid' => '$' . number_format($inv->amount_paid / 100, 2),
                            'status' => $inv->status,
                            'created' => Carbon::createFromTimestamp($inv->created)->toDateString(),
                            'pdf_url' => $inv->invoice_pdf,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to retrieve Stripe invoices: " . $e->getMessage());
            }
        }

        return Inertia::render('billing/dashboard', [
            'subscription' => $sub,
            'usages' => $usages,
            'invoices' => $invoices,
        ]);
    }
}
