<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;
use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class CacheController extends Controller
{
    /**
     * Flush application cache by category.
     */
    public function flush(Request $request)
    {
        $request->validate([
            'category' => ['required', 'string', 'in:all,settings,feature_flags,ip_rules,subscriptions'],
        ]);

        $category = $request->category;
        $message = '';

        try {
            switch ($category) {
                case 'all':
                    Cache::flush();
                    $message = 'Entire application cache has been flushed.';
                    break;

                case 'settings':
                    Setting::flush();
                    $message = 'Settings cache has been flushed.';
                    break;

                case 'feature_flags':
                    // Forget cache for all configured feature flags
                    $flags = FeatureFlag::all(['key']);
                    foreach ($flags as $flag) {
                        Cache::forget("feature_flag:{$flag->key}");
                    }
                    $message = 'Feature flags cache has been flushed.';
                    break;

                case 'ip_rules':
                    Cache::forget('ip_rules');
                    $message = 'IP rules cache has been flushed.';
                    break;

                case 'subscriptions':
                    // Forget subscription cache for all users
                    User::chunk(500, function ($users) {
                        foreach ($users as $user) {
                            Cache::forget("user:{$user->id}:subscription");
                            Cache::forget("user:{$user->id}:feature_limits");
                            Cache::forget("user:{$user->id}:feature_usages");
                        }
                    });
                    $message = 'Subscriptions and usage limits cache has been flushed.';
                    break;
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $message,
                ]);
            }

            return back()->with('status', $message);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
