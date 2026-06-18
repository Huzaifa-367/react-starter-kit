<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;
use App\Models\Setting;

class VerifyTosAccepted
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if ($user) {
            $tosVersion = Setting::get('tos_version');
            if ($tosVersion && $user->terms_version_accepted !== $tosVersion) {
                Inertia::share('tos_acceptance_required', true);
            }
        }

        return $next($request);
    }
}
