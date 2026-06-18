<?php

namespace App\Services;

use App\Models\User;
use App\Models\Plan;
use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class AnalyticsService
{
    public function getMrr(): float
    {
        return Cache::remember('analytics:mrr', 3600, function () {
            $mrr = 0.0;
            $subs = Subscription::whereIn('status', ['active', 'trialing', 'grace'])
                ->with('plan')
                ->get();
            foreach ($subs as $sub) {
                if ($sub->plan) {
                    $price = (float) $sub->plan->price;
                    if ($sub->plan->billing_period === 'monthly') {
                        $mrr += $price;
                    } elseif ($sub->plan->billing_period === 'yearly') {
                        $mrr += $price / 12;
                    }
                }
            }
            return round($mrr, 2);
        });
    }

    public function getArr(): float
    {
        return Cache::remember('analytics:arr', 3600, function () {
            return round($this->getMrr() * 12, 2);
        });
    }

    public function getChurnRate(): float
    {
        return Cache::remember('analytics:churn_rate', 3600, function () {
            $churnedCount = Subscription::whereIn('status', ['canceled', 'expired'])
                ->where('updated_at', '>=', Carbon::now()->subDays(30))
                ->count();

            $activeNowCount = Subscription::whereIn('status', ['active', 'trialing', 'grace'])->count();
            $activeAtStart = $activeNowCount + $churnedCount;

            if ($activeAtStart === 0) {
                return 0.0;
            }

            return round(($churnedCount / $activeAtStart) * 100, 2);
        });
    }

    public function getArpu(): float
    {
        return Cache::remember('analytics:arpu', 3600, function () {
            $activeCount = Subscription::whereIn('status', ['active', 'trialing', 'grace'])->count();
            if ($activeCount === 0) {
                return 0.0;
            }
            return round($this->getMrr() / $activeCount, 2);
        });
    }

    public function getActiveByPlan(): array
    {
        return Cache::remember('analytics:active_by_plan', 3600, function () {
            return Subscription::whereIn('status', ['active', 'trialing', 'grace'])
                ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
                ->groupBy('plans.slug')
                ->selectRaw('plans.slug, count(*) as count')
                ->pluck('count', 'slug')
                ->toArray();
        });
    }

    public function getStatusDistribution(): array
    {
        return Cache::remember('analytics:status_distribution', 3600, function () {
            return Subscription::groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status')
                ->toArray();
        });
    }

    public function getGrowthChart(int $days): array
    {
        return Cache::remember("analytics:growth_chart:{$days}", 3600, function () use ($days) {
            $startDate = Carbon::now()->subDays($days)->startOfDay();
            $results = Subscription::where('created_at', '>=', $startDate)
                ->groupByRaw('DATE(created_at)')
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->pluck('count', 'date')
                ->toArray();

            $chart = [];
            for ($i = $days; $i >= 0; $i--) {
                $dateStr = Carbon::now()->subDays($i)->toDateString();
                $chart[$dateStr] = $results[$dateStr] ?? 0;
            }
            return $chart;
        });
    }

    public function getFunnel(): array
    {
        return Cache::remember('analytics:funnel', 3600, function () {
            $registered = User::count();
            $verified = User::whereNotNull('email_verified_at')->count();
            
            $planSelected = Subscription::distinct('user_id')->count('user_id');
            
            $paid = Subscription::whereIn('status', ['active', 'trialing', 'grace'])
                ->whereHas('plan', fn($q) => $q->where('price', '>', 0))
                ->distinct('user_id')
                ->count('user_id');

            $pctVerified = $registered > 0 ? ($verified / $registered) * 100 : 0;
            $pctPlanSelected = $verified > 0 ? ($planSelected / $verified) * 100 : 0;
            $pctPaid = $planSelected > 0 ? ($paid / $planSelected) * 100 : 0;

            return [
                'registered' => $registered,
                'verified' => $verified,
                'plan_selected' => $planSelected,
                'paid' => $paid,
                'pct_verified' => round($pctVerified, 2),
                'pct_plan_selected' => round($pctPlanSelected, 2),
                'pct_paid' => round($pctPaid, 2),
            ];
        });
    }

    public function getFeatureUsageStats(): array
    {
        return Cache::remember('analytics:feature_usage_stats', 3600, function () {
            $slugs = Feature::pluck('slug')->toArray();
            $stats = [];

            foreach ($slugs as $slug) {
                $usages = SubscriptionUsage::where('feature_slug', $slug)->get();
                $total = $usages->sum('used');
                $avg = $usages->avg('used') ?? 0;
                $max = $usages->max('used') ?? 0;

                $atLimitCount = 0;
                $totalCount = $usages->count();

                foreach ($usages as $u) {
                    $sub = Subscription::with('plan.features')->find($u->subscription_id);
                    if ($sub && $sub->plan) {
                        $limit = $sub->plan->getFeatureValue($slug);
                        if ($limit !== 'unlimited' && $limit !== null && $limit !== 'false' && $limit !== false) {
                            if ($u->used >= (int)$limit) {
                                $atLimitCount++;
                            }
                        }
                    }
                }
                $pctAtLimit = $totalCount > 0 ? ($atLimitCount / $totalCount) * 100 : 0;

                $stats[$slug] = [
                    'total' => (int) $total,
                    'avg' => round((float) $avg, 2),
                    'max' => (int) $max,
                    'pct_at_limit' => round((float) $pctAtLimit, 2),
                ];
            }

            return $stats;
        });
    }

    public function getRevenueByPlan(): array
    {
        return Cache::remember('analytics:revenue_by_plan', 3600, function () {
            $revenue = [];
            $subs = Subscription::whereIn('status', ['active', 'trialing', 'grace'])
                ->with('plan')
                ->get();

            foreach ($subs as $sub) {
                if ($sub->plan) {
                    $name = $sub->plan->name;
                    $price = (float) $sub->plan->price;
                    $monthlyRevenue = 0.0;

                    if ($sub->plan->billing_period === 'monthly') {
                        $monthlyRevenue = $price;
                    } elseif ($sub->plan->billing_period === 'yearly') {
                        $monthlyRevenue = $price / 12;
                    }

                    if (!isset($revenue[$name])) {
                        $revenue[$name] = 0.0;
                    }
                    $revenue[$name] += $monthlyRevenue;
                }
            }

            foreach ($revenue as $name => $val) {
                $revenue[$name] = round($val, 2);
            }

            return $revenue;
        });
    }

    public function getMrrTrend(): array
    {
        return Cache::remember('analytics:mrr_trend', 3600, function () {
            $trend = [];
            for ($i = 11; $i >= 0; $i--) {
                $targetMonth = Carbon::now()->subMonths($i);
                $monthStr = $targetMonth->format('Y-m');
                $startOfMonth = $targetMonth->copy()->startOfMonth();
                $endOfMonth = $targetMonth->copy()->endOfMonth();

                $subs = Subscription::where('created_at', '<=', $endOfMonth)
                    ->where(function ($q) use ($startOfMonth) {
                        $q->whereNull('ends_at')
                          ->orWhere('ends_at', '>=', $startOfMonth);
                    })
                    ->where(function ($q) use ($startOfMonth) {
                        $q->where('status', '!=', 'expired')
                          ->orWhere('updated_at', '>=', $startOfMonth);
                    })
                    ->with('plan')
                    ->get();

                $mrr = 0.0;
                foreach ($subs as $sub) {
                    if ($sub->plan) {
                        $price = (float) $sub->plan->price;
                        if ($sub->plan->billing_period === 'monthly') {
                            $mrr += $price;
                        } elseif ($sub->plan->billing_period === 'yearly') {
                            $mrr += $price / 12;
                        }
                    }
                }
                $trend[$monthStr] = round($mrr, 2);
            }
            return $trend;
        });
    }

    public function getChurnTrend(): array
    {
        return Cache::remember('analytics:churn_trend', 3600, function () {
            $trend = [];
            for ($i = 11; $i >= 0; $i--) {
                $targetMonth = Carbon::now()->subMonths($i);
                $monthStr = $targetMonth->format('Y-m');
                $startOfMonth = $targetMonth->copy()->startOfMonth();
                $endOfMonth = $targetMonth->copy()->endOfMonth();

                $count = Subscription::whereIn('status', ['canceled', 'expired'])
                    ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                    ->count();

                $trend[$monthStr] = $count;
            }
            return $trend;
        });
    }
}
