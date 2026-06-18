<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        // Recursively strip tags from all user inputs, excluding specified keys
        array_walk_recursive($input, function (&$value, $key) {
            if (in_array($key, ['password', 'password_confirmation', 'current_password', 'value'], true)) {
                return;
            }

            if (is_string($value)) {
                $value = strip_tags($value);
            }
        });

        $request->merge($input);

        return $next($request);
    }
}
