<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\ActivityLog;
use App\Services\AnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    protected AnalyticsService $analytics;

    public function __construct(AnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    /**
     * Display the admin dashboard with real database metrics.
     */
    public function index(): Response
    {
        $totalUsers = User::count();
        $activeSubscriptions = Subscription::whereIn('status', ['active', 'trialing', 'grace'])->count();
        $mrr = $this->analytics->getMrr();
        $churnRate = $this->analytics->getChurnRate();

        $recentUsers = User::latest()
            ->take(5)
            ->get(['id', 'name', 'email', 'created_at'])
            ->map(fn(User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toDateString() ?? '',
            ]);

        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn(ActivityLog $log) => [
                'id' => $log->id,
                'description' => $log->description ?: $log->event,
                'user_name' => $log->user ? $log->user->name : 'System',
                'created_at' => $log->created_at->toDateTimeString(),
            ]);

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'total_users' => $totalUsers,
                'active_subscriptions' => $activeSubscriptions,
                'mrr' => $mrr,
                'churn_rate' => $churnRate,
            ],
            'recent_users' => $recentUsers,
            'recent_activities' => $recentActivities,
        ]);
    }
}
