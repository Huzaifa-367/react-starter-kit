<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

class FeatureFlagController extends Controller
{
    /**
     * Display a listing of feature flags.
     */
    public function index()
    {
        $flags = FeatureFlag::latest()->get();
        $plans = Plan::all(['id', 'name', 'slug']);
        $roles = Role::all(['id', 'name']);

        if (request()->wantsJson()) {
            return response()->json([
                'flags' => $flags,
                'plans' => $plans,
                'roles' => $roles,
            ]);
        }

        return Inertia::render('admin/feature-flags/index', [
            'flags' => $flags,
            'plans' => $plans,
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created feature flag in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'key' => ['required', 'string', 'max:100', 'unique:feature_flags,key'],
            'description' => ['nullable', 'string', 'max:500'],
            'enabled_globally' => ['boolean'],
            'enabled_for_plans' => ['nullable', 'array'],
            'enabled_for_roles' => ['nullable', 'array'],
            'enabled_for_users' => ['nullable', 'array'],
        ]);

        $flag = FeatureFlag::create([
            'key' => $request->key,
            'description' => $request->description,
            'enabled_globally' => $request->boolean('enabled_globally'),
            'enabled_for_plans' => $request->enabled_for_plans ?? [],
            'enabled_for_roles' => $request->enabled_for_roles ?? [],
            'enabled_for_users' => $request->enabled_for_users ?? [],
        ]);

        Cache::forget("feature_flag:{$flag->key}");

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Feature flag created successfully.',
                'flag' => $flag,
            ], 201);
        }

        return back()->with('status', 'Feature flag created successfully.');
    }

    /**
     * Update the specified feature flag in storage.
     */
    public function update(Request $request, FeatureFlag $featureFlag)
    {
        $request->validate([
            'key' => ['required', 'string', 'max:100', 'unique:feature_flags,key,' . $featureFlag->id],
            'description' => ['nullable', 'string', 'max:500'],
            'enabled_globally' => ['boolean'],
            'enabled_for_plans' => ['nullable', 'array'],
            'enabled_for_roles' => ['nullable', 'array'],
            'enabled_for_users' => ['nullable', 'array'],
        ]);

        Cache::forget("feature_flag:{$featureFlag->key}");

        $featureFlag->update([
            'key' => $request->key,
            'description' => $request->description,
            'enabled_globally' => $request->boolean('enabled_globally'),
            'enabled_for_plans' => $request->enabled_for_plans ?? [],
            'enabled_for_roles' => $request->enabled_for_roles ?? [],
            'enabled_for_users' => $request->enabled_for_users ?? [],
        ]);

        Cache::forget("feature_flag:{$featureFlag->key}");

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Feature flag updated successfully.',
                'flag' => $featureFlag,
            ]);
        }

        return back()->with('status', 'Feature flag updated successfully.');
    }

    /**
     * Remove the specified feature flag from storage.
     */
    public function destroy(FeatureFlag $featureFlag)
    {
        Cache::forget("feature_flag:{$featureFlag->key}");
        $featureFlag->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Feature flag deleted successfully.',
            ]);
        }

        return back()->with('status', 'Feature flag deleted successfully.');
    }
}
