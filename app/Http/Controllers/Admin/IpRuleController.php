<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpRule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class IpRuleController extends Controller
{
    /**
     * Display a listing of IP rules.
     */
    public function index()
    {
        $rules = IpRule::latest()->paginate(20);

        if (request()->wantsJson()) {
            return response()->json($rules);
        }

        return Inertia::render('admin/ip-rules/index', [
            'rules' => $rules,
        ]);
    }

    /**
     * Store a newly created IP rule in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'ip' => ['required', 'string', 'max:50', function ($attribute, $value, $fail) {
                // Validate IP or CIDR (IPv4 and IPv6)
                if (filter_var($value, FILTER_VALIDATE_IP) === false) {
                    $parts = explode('/', $value);
                    if (count($parts) !== 2 || filter_var($parts[0], FILTER_VALIDATE_IP) === false || !is_numeric($parts[1])) {
                        $fail('The ' . $attribute . ' must be a valid IP address or CIDR range.');
                        return;
                    }
                    $isIpv6 = filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
                    $maxMask = $isIpv6 ? 128 : 32;
                    $mask = (int) $parts[1];
                    if ($mask < 0 || $mask > $maxMask) {
                        $fail('The ' . $attribute . ' CIDR mask must be between 0 and ' . $maxMask . '.');
                    }
                }
            }],
            'type' => ['required', 'in:allow,block'],
            'reason' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $rule = IpRule::create([
            'ip' => trim($request->ip),
            'type' => $request->type,
            'reason' => $request->reason,
            'is_active' => $request->boolean('is_active', true),
        ]);

        Cache::forget('ip_rules');

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'IP rule created successfully.',
                'rule' => $rule,
            ], 201);
        }

        return back()->with('status', 'IP rule created successfully.');
    }

    /**
     * Update the specified IP rule in storage.
     */
    public function update(Request $request, IpRule $ipRule)
    {
        $request->validate([
            'ip' => ['required', 'string', 'max:50', function ($attribute, $value, $fail) {
                // Validate IP or CIDR (IPv4 and IPv6)
                if (filter_var($value, FILTER_VALIDATE_IP) === false) {
                    $parts = explode('/', $value);
                    if (count($parts) !== 2 || filter_var($parts[0], FILTER_VALIDATE_IP) === false || !is_numeric($parts[1])) {
                        $fail('The ' . $attribute . ' must be a valid IP address or CIDR range.');
                        return;
                    }
                    $isIpv6 = filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
                    $maxMask = $isIpv6 ? 128 : 32;
                    $mask = (int) $parts[1];
                    if ($mask < 0 || $mask > $maxMask) {
                        $fail('The ' . $attribute . ' CIDR mask must be between 0 and ' . $maxMask . '.');
                    }
                }
            }],
            'type' => ['required', 'in:allow,block'],
            'reason' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $ipRule->update([
            'ip' => trim($request->ip),
            'type' => $request->type,
            'reason' => $request->reason,
            'is_active' => $request->boolean('is_active'),
        ]);

        Cache::forget('ip_rules');

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'IP rule updated successfully.',
                'rule' => $ipRule,
            ]);
        }

        return back()->with('status', 'IP rule updated successfully.');
    }

    /**
     * Remove the specified IP rule from storage.
     */
    public function destroy(IpRule $ipRule)
    {
        $ipRule->delete();

        Cache::forget('ip_rules');

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'IP rule deleted successfully.',
            ]);
        }

        return back()->with('status', 'IP rule deleted successfully.');
    }
}
