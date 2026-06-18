<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Feature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPlanController extends Controller
{
    /**
     * Display a listing of the plans.
     */
    public function index(): Response
    {
        $plans = Plan::with('features')
            ->withCount(['subscriptions' => function ($q) {
                $q->where('status', 'active');
            }])
            ->orderBy('sort_order', 'asc')
            ->get();

        $features = Feature::all(['id', 'name', 'slug']);

        return Inertia::render('admin/plans/index', [
            'plans' => $plans,
            'features' => $features,
        ]);
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:plans,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_period' => ['required', 'in:monthly,yearly,lifetime'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'grace_days' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'stripe_product_id' => ['nullable', 'string'],
            'stripe_monthly_price_id' => ['nullable', 'string'],
            'stripe_yearly_price_id' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
        ]);

        $plan = Plan::create($request->only([
            'name', 'slug', 'description', 'price', 'currency', 'billing_period',
            'trial_days', 'grace_days', 'sort_order', 'is_active',
            'stripe_product_id', 'stripe_monthly_price_id', 'stripe_yearly_price_id'
        ]));

        if ($request->filled('features')) {
            $pivotData = [];
            foreach ($request->features as $featureId => $value) {
                $pivotData[$featureId] = ['value' => $value];
            }
            $plan->features()->sync($pivotData);
        }

        return back()->with('status', 'Plan created successfully.');
    }

    /**
     * Update the specified plan details.
     */
    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:plans,slug,' . $plan->id],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_period' => ['required', 'in:monthly,yearly,lifetime'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'grace_days' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'stripe_product_id' => ['nullable', 'string'],
            'stripe_monthly_price_id' => ['nullable', 'string'],
            'stripe_yearly_price_id' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
        ]);

        $plan->update($request->only([
            'name', 'slug', 'description', 'price', 'currency', 'billing_period',
            'trial_days', 'grace_days', 'sort_order', 'is_active',
            'stripe_product_id', 'stripe_monthly_price_id', 'stripe_yearly_price_id'
        ]));

        if ($request->has('features')) {
            $pivotData = [];
            foreach ($request->features ?? [] as $featureId => $value) {
                $pivotData[$featureId] = ['value' => $value];
            }
            $plan->features()->sync($pivotData);
        }

        return back()->with('status', 'Plan updated successfully.');
    }

    /**
     * Sync features for the plan.
     */
    public function syncFeatures(Request $request, Plan $plan): RedirectResponse
    {
        $request->validate([
            'features' => ['required', 'array'],
        ]);

        $pivotData = [];
        foreach ($request->features as $featureId => $value) {
            $pivotData[$featureId] = ['value' => $value];
        }

        $plan->features()->sync($pivotData);

        return back()->with('status', 'Plan features synchronized successfully.');
    }

    /**
     * Toggle active status of a plan.
     */
    public function toggleActive(Plan $plan): RedirectResponse
    {
        $plan->update([
            'is_active' => !$plan->is_active,
        ]);

        return back()->with('status', 'Plan active state toggled.');
    }

    /**
     * Remove the specified plan from storage.
     */
    public function destroy(Plan $plan): RedirectResponse
    {
        $activeSubscribersCount = $plan->subscriptions()->where('status', 'active')->count();

        if ($activeSubscribersCount > 0) {
            return back()->withErrors(['error' => 'You cannot delete a plan that has active subscribers.']);
        }

        $plan->features()->detach();
        $plan->delete();

        return back()->with('status', 'Plan deleted successfully.');
    }
}
