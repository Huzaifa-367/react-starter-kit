<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    /**
     * Display a listing of the coupons.
     */
    public function index()
    {
        $coupons = Coupon::latest()->paginate(15);

        if (request()->wantsJson()) {
            return response()->json($coupons);
        }

        return Inertia::render('admin/coupons/index', [
            'coupons' => $coupons,
        ]);
    }

    /**
     * Store a newly created coupon in storage and sync with Stripe.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code'],
            'discount_type' => ['required', 'in:percent,amount'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'duration' => ['required', 'in:once,repeating,forever'],
            'duration_in_months' => ['nullable', 'integer', 'min:1'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'valid_until' => ['nullable', 'date', 'after:today'],
        ]);

        $stripeSecret = Setting::get('stripe_secret') ?: env('STRIPE_SECRET');
        $stripeCouponId = null;

        if ($stripeSecret) {
            \Stripe\Stripe::setApiKey($stripeSecret);

            $stripeParams = [
                'id' => $request->code,
                'duration' => $request->duration,
            ];

            if ($request->discount_type === 'percent') {
                $stripeParams['percent_off'] = (float) $request->discount_value;
            } else {
                $stripeParams['amount_off'] = (int) ($request->discount_value * 100);
                $stripeParams['currency'] = Setting::get('app_currency', 'USD');
            }

            if ($request->filled('max_redemptions')) {
                $stripeParams['max_redemptions'] = $request->max_redemptions;
            }

            if ($request->filled('valid_until')) {
                $stripeParams['redeem_by'] = \Carbon\Carbon::parse($request->valid_until)->timestamp;
            }

            if ($request->duration === 'repeating') {
                $stripeParams['duration_in_months'] = $request->input('duration_in_months', 12);
            }

            try {
                $stripeCoupon = \Stripe\Coupon::create($stripeParams);
                $stripeCouponId = $stripeCoupon->id;
            } catch (\Exception $e) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => 'Stripe integration: ' . $e->getMessage()], 422);
                }
                return back()->withErrors(['code' => 'Stripe integration error: ' . $e->getMessage()]);
            }
        } else {
            // Fallback if Stripe is not configured: use the code itself as the Stripe coupon ID
            $stripeCouponId = $request->code;
        }

        $coupon = Coupon::create([
            'stripe_coupon_id' => $stripeCouponId,
            'code' => $request->code,
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'duration' => $request->duration,
            'max_redemptions' => $request->max_redemptions,
            'valid_until' => $request->valid_until,
            'is_active' => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Coupon created successfully.',
                'coupon' => $coupon
            ], 201);
        }

        return back()->with('status', 'Coupon created successfully.');
    }

    /**
     * Display the specified coupon.
     */
    public function show(Coupon $coupon)
    {
        if (request()->wantsJson()) {
            return response()->json($coupon);
        }

        return Inertia::render('admin/coupons/show', [
            'coupon' => $coupon,
        ]);
    }

    /**
     * Update the specified coupon (only settings, as Stripe coupons are immutable).
     */
    public function update(Request $request, Coupon $coupon)
    {
        $request->validate([
            'is_active' => ['boolean'],
            'valid_until' => ['nullable', 'date'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
        ]);

        $coupon->update($request->only(['is_active', 'valid_until', 'max_redemptions']));

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Coupon updated successfully.',
                'coupon' => $coupon
            ]);
        }

        return back()->with('status', 'Coupon updated successfully.');
    }

    /**
     * Toggle the active state of the coupon.
     */
    public function toggle(Coupon $coupon)
    {
        $coupon->update([
            'is_active' => !$coupon->is_active,
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Coupon active state toggled.',
                'coupon' => $coupon
            ]);
        }

        return back()->with('status', 'Coupon active state toggled.');
    }

    /**
     * Remove the specified coupon from storage and Stripe.
     */
    public function destroy(Coupon $coupon)
    {
        $stripeSecret = Setting::get('stripe_secret') ?: env('STRIPE_SECRET');

        if ($stripeSecret && $coupon->stripe_coupon_id) {
            \Stripe\Stripe::setApiKey($stripeSecret);
            try {
                \Stripe\Coupon::retrieve($coupon->stripe_coupon_id)->delete();
            } catch (\Exception $e) {
                // If it doesn't exist in stripe, ignore
            }
        }

        $coupon->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Coupon deleted successfully.'
            ]);
        }

        return back()->with('status', 'Coupon deleted successfully.');
    }
}
