<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class SessionController extends Controller
{
    /**
     * Display a listing of the user's active sessions.
     */
    public function index(Request $request): Response
    {
        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) use ($request) {
                [$browser, $platform] = $this->parseUserAgent($session->user_agent ?? '');

                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'is_current_device' => $session->id === $request->session()->getId(),
                    'browser' => $browser,
                    'platform' => $platform,
                    'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                ];
            });

        return Inertia::render('profile/sessions', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Terminate the specified session.
     */
    public function destroy(Request $request, string $sessionId): RedirectResponse
    {
        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', $sessionId)
            ->delete();

        return back()->with('status', 'Session terminated.');
    }

    /**
     * Terminate all other sessions except the current one.
     */
    public function destroyOthers(Request $request): RedirectResponse
    {
        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('status', 'Other sessions terminated.');
    }

    /**
     * Basic User Agent parser.
     */
    private function parseUserAgent(string $userAgent): array
    {
        $browser = 'Unknown Browser';
        $platform = 'Unknown OS';

        if (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/MSIE/i', $userAgent) || preg_match('/Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = 'Opera';
        }

        if (preg_match('/Windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            $platform = 'iOS';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $platform = 'Android';
        }

        return [$browser, $platform];
    }
}
