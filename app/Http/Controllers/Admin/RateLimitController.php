<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class RateLimitController extends Controller
{
    /**
     * Display a listing of active rate limit locks from the cache.
     */
    public function index()
    {
        $lockedItems = [];
        
        // Scan cache keys for throttle patterns if using redis
        $cacheDriver = config('cache.default');
        
        if ($cacheDriver === 'redis') {
            try {
                $redis = Redis::connection();
                $prefix = config('database.redis.options.prefix', '');
                
                // Scan for keys containing throttle
                $rawKeys = $redis->keys($prefix . '*throttle*');
                
                foreach ($rawKeys as $key) {
                    if ($prefix && str_starts_with($key, $prefix)) {
                        $key = substr($key, strlen($prefix));
                    }
                    
                    // Remove cache prefix
                    $cacheKey = str_replace(config('cache.prefix', 'laravel') . ':', '', $key);
                    
                    $hits = Cache::get($cacheKey);
                    $ttl = $redis->ttl($prefix . $cacheKey);
                    
                    if ($ttl > 0) {
                        $lockedItems[] = [
                            'key' => $cacheKey,
                            'hits' => $hits,
                            'ttl' => $ttl,
                            'expires_at' => now()->addSeconds($ttl)->toIso8601String(),
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Redis scan failed, fallback to empty list or log
            }
        } else {
            // For other cache drivers, we cannot scan keys, but we can provide instructions or empty list
        }

        if (request()->wantsJson()) {
            return response()->json($lockedItems);
        }

        return Inertia::render('admin/rate-limits/index', [
            'locks' => $lockedItems,
        ]);
    }

    /**
     * Unlock a rate-limited resource.
     */
    public function unlock(Request $request)
    {
        $request->validate([
            'key' => ['required', 'string'],
        ]);

        Cache::forget($request->key);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Rate limit lock removed successfully.',
            ]);
        }

        return back()->with('status', 'Rate limit lock removed successfully.');
    }
}
