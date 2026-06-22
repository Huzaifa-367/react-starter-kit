<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

use App\Models\Setting;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Cache;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Eager load roles and onboarding if logged in
        if ($user) {
            $user->loadMissing(['roles', 'onboarding', 'activeSubscription.plan.features']);
        }

        // Cache app branding settings for 24 hours
        $branding = Cache::remember('app_branding', 86400, function () {
            return [
                'appName' => Setting::get('brand_app_name', config('app.name', 'SaaS App')),
                'logoUrl' => Setting::get('brand_logo_url', '/images/logo.png'),
                'faviconUrl' => Setting::get('brand_favicon_url', '/favicon.ico'),
                'primaryColor' => Setting::get('brand_primary_color', '#4F46E5'),
                'supportEmail' => Setting::get('brand_support_email', 'support@example.com'),
                'footerText' => Setting::get('brand_footer_text', '© ' . date('Y') . ' SaaS App'),
            ];
        });

        // Flash messages
        $flash = [
            'success' => $request->session()->get('success'),
            'error' => $request->session()->get('error'),
            'status' => $request->session()->get('status'),
        ];

        // Impersonation info
        $isImpersonating = $request->session()->has('impersonating_admin_id');
        $impersonatingAs = null;
        if ($isImpersonating && $user) {
            $impersonatingAs = $user->name;
        }

        // Unread notifications count (cache for 60s)
        $unreadCount = 0;
        if ($user) {
            $unreadCount = Cache::remember("user:{$user->id}:unread_count", 60, function () use ($user) {
                return UserNotification::where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count();
            });
        }

        // Announcement banner info
        $announcement = [
            'text' => Setting::get('announcement_text'),
            'type' => Setting::get('announcement_type', 'info'),
            'active' => (bool) Setting::get('announcement_active', false),
            'dismissible' => (bool) Setting::get('announcement_dismissible', true),
        ];

        // Terms of Service info
        $tosVersion = Setting::get('tos_version', 'v1.0');
        $tos = [
            'acceptance_required' => $user ? ($tosVersion !== $user->terms_version_accepted) : false,
            'tos_url' => Setting::get('tos_url', '#'),
            'privacy_url' => Setting::get('privacy_url', '#'),
        ];

        return [
            ...parent::share($request),
            'name' => $branding['appName'],
            'auth' => [
                'user' => $user,
            ],
            'branding' => $branding,
            'onboarding' => $user ? $user->onboarding : null,
            'announcement' => $announcement,
            'tos' => $tos,
            'flash' => $flash,
            'is_impersonating' => $isImpersonating,
            'impersonating_as' => $impersonatingAs,
            'unread_notifications' => $unreadCount,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
