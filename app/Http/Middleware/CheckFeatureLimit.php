<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckFeatureLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $featureSlug): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        if ($user->isStaff()) {
            return $next($request);
        }

        if (!$user->canUseFeature($featureSlug)) {
            $limit = $user->getFeatureLimit($featureSlug);
            $used = $user->getFeatureUsage($featureSlug);

            return $request->expectsJson() || $request->wantsJson()
                ? response()->json([
                    'message' => 'Feature limit reached',
                    'feature' => $featureSlug,
                    'limit' => $limit,
                    'used' => $used,
                ], 403)
                : back()->with('error', "You've reached your {$featureSlug} limit.");
        }

        return $next($request);
    }
}
