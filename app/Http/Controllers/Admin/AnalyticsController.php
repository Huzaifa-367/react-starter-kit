<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analytics;

    public function __construct(AnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    /**
     * Display the business and subscription analytics dashboard.
     */
    public function index(Request $request): Response
    {
        $days = (int) $request->input('days', 30);

        return Inertia::render('admin/analytics/index', [
            'mrr' => $this->analytics->getMrr(),
            'arr' => $this->analytics->getArr(),
            'churnRate' => $this->analytics->getChurnRate(),
            'arpu' => $this->analytics->getArpu(),
            'activeByPlan' => $this->analytics->getActiveByPlan(),
            'statusDistribution' => $this->analytics->getStatusDistribution(),
            'growthChart' => $this->analytics->getGrowthChart($days),
            'funnel' => $this->analytics->getFunnel(),
            'featureUsageStats' => $this->analytics->getFeatureUsageStats(),
            'revenueByPlan' => $this->analytics->getRevenueByPlan(),
            'mrrTrend' => $this->analytics->getMrrTrend(),
            'churnTrend' => $this->analytics->getChurnTrend(),
            'days' => $days,
        ]);
    }

    /**
     * Export all key analytics matrices to a CSV file.
     */
    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="analytics_summary_' . time() . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['METRIC', 'VALUE']);
            fputcsv($file, ['Monthly Recurring Revenue (MRR)', '$' . number_format($this->analytics->getMrr(), 2)]);
            fputcsv($file, ['Annual Recurring Revenue (ARR)', '$' . number_format($this->analytics->getArr(), 2)]);
            fputcsv($file, ['Average Revenue Per User (ARPU)', '$' . number_format($this->analytics->getArpu(), 2)]);
            fputcsv($file, ['Churn Rate (%)', $this->analytics->getChurnRate() . '%']);
            fputcsv($file, []);

            fputcsv($file, ['REVENUE BY PLAN', 'MONTHLY REVENUE']);
            foreach ($this->analytics->getRevenueByPlan() as $plan => $revenue) {
                fputcsv($file, [$plan, '$' . number_format($revenue, 2)]);
            }
            fputcsv($file, []);

            fputcsv($file, ['PLAN SLUG', 'ACTIVE SUBSCRIBER COUNT']);
            foreach ($this->analytics->getActiveByPlan() as $slug => $count) {
                fputcsv($file, [$slug, $count]);
            }
            fputcsv($file, []);

            fputcsv($file, ['SUBSCRIPTION STATUS', 'COUNT']);
            foreach ($this->analytics->getStatusDistribution() as $status => $count) {
                fputcsv($file, [$status, $count]);
            }
            fputcsv($file, []);

            $funnel = $this->analytics->getFunnel();
            fputcsv($file, ['FUNNEL STAGE', 'RECORDS', 'CONVERSION RATE (%)']);
            fputcsv($file, ['Registered Users', $funnel['registered'], '100%']);
            fputcsv($file, ['Verified Users', $funnel['verified'], $funnel['pct_verified'] . '%']);
            fputcsv($file, ['Plan Selected', $funnel['plan_selected'], $funnel['pct_plan_selected'] . '%']);
            fputcsv($file, ['Paid Active Subscriptions', $funnel['paid'], $funnel['pct_paid'] . '%']);
            fputcsv($file, []);

            fputcsv($file, ['FEATURE SLUG', 'TOTAL USES', 'AVG USES', 'MAX USES', '% AT LIMIT']);
            foreach ($this->analytics->getFeatureUsageStats() as $slug => $stats) {
                fputcsv($file, [
                    $slug,
                    $stats['total'],
                    $stats['avg'],
                    $stats['max'],
                    $stats['pct_at_limit'] . '%',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
