<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use App\Models\IpRule;

class IpFirewall
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        if (empty($ip)) {
            return $next($request);
        }

        // Cache IP rules for 1 hour
        $rules = Cache::remember('ip_rules', 3600, function () {
            return IpRule::active()
                ->get(['ip', 'type'])
                ->toArray();
        });

        // 1. Check block rules first
        foreach ($rules as $rule) {
            if ($rule['type'] === 'block' && $this->ipInCidr($ip, $rule['ip'])) {
                abort(403, 'Your IP address has been blocked.');
            }
        }

        // 2. Check if allow rules exist (if so, IP must match at least one allow rule)
        $hasAllowRules = false;
        $matchedAllow = false;

        foreach ($rules as $rule) {
            if ($rule['type'] === 'allow') {
                $hasAllowRules = true;
                if ($this->ipInCidr($ip, $rule['ip'])) {
                    $matchedAllow = true;
                    break;
                }
            }
        }

        if ($hasAllowRules && !$matchedAllow) {
            abort(403, 'Your IP address is not authorized to access this resource.');
        }

        return $next($request);
    }

    /**
     * Determine if an IP address is within a CIDR range or matches single IP.
     */
    protected function ipInCidr(string $ip, string $cidr): bool
    {
        $ip = trim($ip);
        $cidr = trim($cidr);

        if ($ip === $cidr) {
            return true;
        }

        if (!str_contains($cidr, '/')) {
            return false;
        }

        // Check if both subnet and IP are valid IPv4 addresses to prevent ip2long warnings
        list($subnet, $mask) = explode('/', $cidr);
        
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || 
            !filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskInt = (int) $mask;

        if ($ipLong === false || $subnetLong === false || $maskInt < 0 || $maskInt > 32) {
            return false;
        }

        $maskLong = ~((1 << (32 - $maskInt)) - 1);
        
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
