<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;
use Illuminate\Support\Carbon;

class TrackImpersonation
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && $request->session()->has('impersonating_admin_id')) {
            $startedAt = $request->session()->get('impersonation_started_at');

            // Expire impersonation after 2 hours
            if ($startedAt && Carbon::parse($startedAt)->addHours(2)->isPast()) {
                $request->session()->forget(['impersonating_admin_id', 'impersonation_started_at']);
            } else {
                $user = auth()->user();
                if ($user) {
                    Inertia::share('is_impersonating', true);
                    Inertia::share('impersonating_as', $user->name);
                }
            }
        }

        return $next($request);
    }
}
