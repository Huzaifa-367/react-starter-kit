import React from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    DollarSign,
    Users,
    ArrowDownRight,
    TrendingUp,
    Download,
    Calendar,
} from 'lucide-react';
import {
    ResponsiveContainer,
    AreaChart,
    Area,
    XAxis,
    YAxis,
    Tooltip,
    CartesianGrid,
    BarChart,
    Bar,
    PieChart,
    Pie,
    Cell,
    Legend,
    LineChart,
    Line,
} from 'recharts';

interface Props {
    mrr: number;
    arr: number;
    churnRate: number;
    arpu: number;
    activeByPlan: Record<string, number>;
    statusDistribution: Record<string, number>;
    growthChart: Record<string, number>;
    funnel: {
        registered: number;
        verified: number;
        plan_selected: number;
        paid: number;
        pct_verified: number;
        pct_plan_selected: number;
        pct_paid: number;
    };
    featureUsageStats: Record<string, {
        total: number;
        avg: number;
        max: number;
        pct_at_limit: number;
    }>;
    revenueByPlan: Record<string, number>;
    mrrTrend: Record<string, number>;
    churnTrend: Record<string, number>;
    days: number;
}

export default function AnalyticsIndex({
    mrr,
    arr,
    churnRate,
    arpu,
    activeByPlan,
    statusDistribution,
    growthChart,
    funnel,
    featureUsageStats,
    revenueByPlan,
    mrrTrend,
    churnTrend,
    days,
}: Props) {
    const handleDaysChange = (d: number) => {
        router.get('/admin/analytics', { days: d }, { preserveState: true });
    };

    const handleExport = () => {
        window.open('/admin/analytics/export', '_blank');
    };

    const formatCurrency = (val: number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(val);
    };

    // Format Data for Recharts
    const mrrTrendData = Object.entries(mrrTrend).map(([month, value]) => ({
        month,
        mrr: value,
    }));

    const churnTrendData = Object.entries(churnTrend).map(([month, value]) => ({
        month,
        churn: value,
    }));

    const growthData = Object.entries(growthChart).map(([date, count]) => ({
        date: date.substring(5), // truncate YYYY-
        subscriptions: count,
    }));

    const planRevenueColors = ['#4F46E5', '#10B981', '#F59E0B', '#3B82F6', '#EC4899', '#8B5CF6'];
    const revenueData = Object.entries(revenueByPlan).map(([name, value]) => ({
        name,
        value,
    }));

    const activeByPlanData = Object.entries(activeByPlan).map(([slug, count]) => ({
        name: slug.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase()),
        count,
    }));

    const statusColors: Record<string, string> = {
        active: '#10B981',
        trialing: '#3B82F6',
        grace: '#F59E0B',
        canceled: '#F43F5E',
        expired: '#6B7280',
        paused: '#8B5CF6',
    };
    const statusData = Object.entries(statusDistribution).map(([status, count]) => ({
        name: status.charAt(0).toUpperCase() + status.slice(1),
        value: count,
        color: statusColors[status] || '#6B7280',
    }));

    const funnelData = [
        { stage: 'Registered', count: funnel.registered, rate: '100%' },
        { stage: 'Verified', count: funnel.verified, rate: `${funnel.pct_verified}%` },
        { stage: 'Plan Selected', count: funnel.plan_selected, rate: `${funnel.pct_plan_selected}%` },
        { stage: 'Paid Subscription', count: funnel.paid, rate: `${funnel.pct_paid}%` },
    ];

    return (
        <>
            <Head title="SaaS Analytics" />

            <div className="flex-1 space-y-8 p-6 md:p-8">
                {/* Header */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 className="text-3xl font-extrabold tracking-tight text-foreground">SaaS Analytics</h1>
                        <p className="text-muted-foreground text-sm mt-1">
                            Deep metrics mapping subscription performance, MRR growth, churn trends, and feature usage.
                        </p>
                    </div>
                    <div className="flex items-center gap-2 flex-wrap">
                        <div className="bg-muted p-1 rounded-md flex gap-1 border border-border">
                            {[7, 30, 90].map((d) => (
                                <Button
                                    key={d}
                                    variant={days === d ? 'default' : 'ghost'}
                                    size="sm"
                                    onClick={() => handleDaysChange(d)}
                                    className="h-8 text-xs cursor-pointer"
                                >
                                    {d} Days
                                </Button>
                            ))}
                        </div>
                        <Button variant="outline" size="sm" onClick={handleExport} className="h-9 cursor-pointer">
                            <Download className="mr-2 h-4 w-4 text-primary" />
                            Export Data Summary
                        </Button>
                    </div>
                </div>

                {/* KPI Metrics */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Card className="border border-border">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                Monthly Recurring Revenue (MRR)
                            </CardTitle>
                            <DollarSign className="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-extrabold text-foreground">{formatCurrency(mrr)}</div>
                            <p className="text-[10px] text-emerald-500 font-semibold mt-1">
                                Annualised Projection: {formatCurrency(arr)} / yr
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border border-border">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                Average Revenue Per User (ARPU)
                            </CardTitle>
                            <TrendingUp className="h-4 w-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-extrabold text-foreground">{formatCurrency(arpu)}</div>
                            <p className="text-[10px] text-muted-foreground mt-1">
                                Across all active accounts
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border border-border">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                Platform Churn Rate
                            </CardTitle>
                            <ArrowDownRight className="h-4 w-4 text-rose-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-extrabold text-foreground">{churnRate}%</div>
                            <p className="text-[10px] text-rose-400 font-semibold mt-1">
                                Cancellations in past 30 days
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border border-border">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                Total Paid Active Users
                            </CardTitle>
                            <Users className="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-extrabold text-foreground">{funnel.paid}</div>
                            <p className="text-[10px] text-emerald-500 font-semibold mt-1">
                                Excluding unverified & free starter
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Growth and Financial trends */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {/* MRR Trend */}
                    <Card className="border border-border">
                        <CardHeader>
                            <CardTitle className="text-base font-bold">12-Month MRR Trend</CardTitle>
                            <CardDescription>Estimated monthly recurring revenue growth.</CardDescription>
                        </CardHeader>
                        <CardContent className="h-[300px]">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={mrrTrendData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                                    <defs>
                                        <linearGradient id="colorMrr" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#4F46E5" stopOpacity={0.2}/>
                                            <stop offset="95%" stopColor="#4F46E5" stopOpacity={0}/>
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" opacity={0.15} />
                                    <XAxis dataKey="month" stroke="#888888" fontSize={11} />
                                    <YAxis stroke="#888888" fontSize={11} tickFormatter={(v) => `$${v}`} />
                                    <Tooltip formatter={(value: any) => [`$${value}`, 'MRR']} />
                                    <Area type="monotone" dataKey="mrr" stroke="#4F46E5" strokeWidth={2.5} fillOpacity={1} fill="url(#colorMrr)" />
                                </AreaChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {/* Subscription Signup Growth */}
                    <Card className="border border-border">
                        <CardHeader>
                            <CardTitle className="text-base font-bold">New Subscriptions Growth</CardTitle>
                            <CardDescription>Daily signup creation rate over the selected timeframe.</CardDescription>
                        </CardHeader>
                        <CardContent className="h-[300px]">
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={growthData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" opacity={0.15} />
                                    <XAxis dataKey="date" stroke="#888888" fontSize={10} />
                                    <YAxis stroke="#888888" fontSize={11} allowDecimals={false} />
                                    <Tooltip />
                                    <Line type="monotone" dataKey="subscriptions" stroke="#10B981" strokeWidth={2.5} dot={{ r: 2 }} activeDot={{ r: 4 }} />
                                </LineChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>

                {/* Middle segment: Plan distributions */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Revenue by Plan */}
                    <Card className="border border-border flex flex-col justify-between">
                        <CardHeader>
                            <CardTitle className="text-base font-bold">MRR Contribution by Plan</CardTitle>
                            <CardDescription>Where recurring monthly fees originate from.</CardDescription>
                        </CardHeader>
                        <CardContent className="h-[240px] flex items-center justify-center relative">
                            {revenueData.length === 0 ? (
                                <span className="text-muted-foreground text-xs">No active paid plans found.</span>
                            ) : (
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={revenueData}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={60}
                                            outerRadius={80}
                                            paddingAngle={2}
                                            dataKey="value"
                                        >
                                            {revenueData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={planRevenueColors[index % planRevenueColors.length]} />
                                            ))}
                                        </Pie>
                                        <Tooltip formatter={(value: any) => [`$${value}`, 'MRR']} />
                                        <Legend verticalAlign="bottom" height={36} iconSize={10} iconType="circle" wrapperStyle={{ fontSize: 10 }} />
                                    </PieChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>

                    {/* Subscription Status distribution */}
                    <Card className="border border-border flex flex-col justify-between">
                        <CardHeader>
                            <CardTitle className="text-base font-bold">Subscription Status</CardTitle>
                            <CardDescription>Active lifecycle segments distribution.</CardDescription>
                        </CardHeader>
                        <CardContent className="h-[240px] flex items-center justify-center">
                            {statusData.length === 0 ? (
                                <span className="text-muted-foreground text-xs font-mono">No subscription records found.</span>
                            ) : (
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={statusData}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={0}
                                            outerRadius={75}
                                            dataKey="value"
                                            label={({ name, percent }) => percent > 0.05 ? `${name} (${(percent * 100).toFixed(0)}%)` : ''}
                                            labelLine={false}
                                        >
                                            {statusData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={entry.color} />
                                            ))}
                                        </Pie>
                                        <Tooltip />
                                    </PieChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>

                    {/* Subscribers by plan */}
                    <Card className="border border-border flex flex-col justify-between">
                        <CardHeader>
                            <CardTitle className="text-base font-bold">Active Subscriptions Count</CardTitle>
                            <CardDescription>Volume count per plan.</CardDescription>
                        </CardHeader>
                        <CardContent className="h-[240px]">
                            {activeByPlanData.length === 0 ? (
                                <div className="h-full flex items-center justify-center text-muted-foreground text-xs">No active subs.</div>
                            ) : (
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={activeByPlanData} layout="vertical" margin={{ top: 10, right: 10, left: 10, bottom: 10 }}>
                                        <CartesianGrid strokeDasharray="3 3" opacity={0.1} horizontal={false} />
                                        <XAxis type="number" stroke="#888888" fontSize={10} allowDecimals={false} />
                                        <YAxis dataKey="name" type="category" stroke="#888888" fontSize={9} width={80} />
                                        <Tooltip />
                                        <Bar dataKey="count" fill="#4F46E5" radius={[0, 4, 4, 0]} barSize={12} />
                                    </BarChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Bottom row: Funnel and Churn trend */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* User Conversion Funnel */}
                    <Card className="lg:col-span-1 border border-border">
                        <CardHeader>
                            <CardTitle className="text-base font-bold">Conversion Funnel</CardTitle>
                            <CardDescription>User activation & payment retention funnel rates.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-4">
                                {funnelData.map((item, idx) => (
                                    <div key={idx} className="space-y-1">
                                        <div className="flex justify-between items-center text-xs font-semibold">
                                            <span className="text-foreground">{item.stage}</span>
                                            <span className="text-muted-foreground">{item.count} ({item.rate})</span>
                                        </div>
                                        <div className="w-full bg-muted rounded-full h-2">
                                            <div
                                                className="bg-primary h-2 rounded-full transition-all duration-500"
                                                style={{ width: item.rate }}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Churn Volume Trend */}
                    <Card className="border border-border">
                        <CardHeader>
                            <CardTitle className="text-base font-bold">Monthly Churn Volume</CardTitle>
                            <CardDescription>Cancellations & expiries counts over the past year.</CardDescription>
                        </CardHeader>
                        <CardContent className="h-[200px]">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={churnTrendData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" opacity={0.1} />
                                    <XAxis dataKey="month" stroke="#888888" fontSize={10} />
                                    <YAxis stroke="#888888" fontSize={11} allowDecimals={false} />
                                    <Tooltip />
                                    <Bar dataKey="churn" fill="#F43F5E" radius={[4, 4, 0, 0]} barSize={20} />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {/* Feature limits usage table */}
                    <Card className="border border-border">
                        <CardHeader>
                            <CardTitle className="text-base font-bold">Feature Usage Analytics</CardTitle>
                            <CardDescription>Heatmap showing percentage of limit consumed.</CardDescription>
                        </CardHeader>
                        <CardContent className="p-0 overflow-y-auto max-h-[200px]">
                            <table className="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr className="bg-muted/40 text-muted-foreground font-bold border-b border-border">
                                        <th className="p-3">Feature Slug</th>
                                        <th className="p-3 text-right">Total Uses</th>
                                        <th className="p-3 text-right">Max Limit Hits</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {Object.entries(featureUsageStats).length === 0 ? (
                                        <tr>
                                            <td colSpan={3} className="p-4 text-center text-muted-foreground">
                                                No features metrics recorded.
                                            </td>
                                        </tr>
                                    ) : (
                                        Object.entries(featureUsageStats).map(([slug, stat]) => (
                                            <tr key={slug} className="hover:bg-muted/10">
                                                <td className="p-3 font-semibold font-mono text-primary">{slug}</td>
                                                <td className="p-3 text-right text-muted-foreground font-mono">{stat.total.toLocaleString()}</td>
                                                <td className="p-3 text-right font-mono">
                                                    <span className={`px-1.5 py-0.5 rounded font-bold ${
                                                        stat.pct_at_limit > 50 ? 'bg-rose-500/10 text-rose-500' :
                                                        stat.pct_at_limit > 10 ? 'bg-amber-500/10 text-amber-500' :
                                                        'bg-emerald-500/10 text-emerald-500'
                                                    }`}>
                                                        {stat.pct_at_limit}%
                                                    </span>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

AnalyticsIndex.layout = {
    breadcrumbs: [
        {
            title: 'SaaS Analytics',
            href: '/admin/analytics',
        },
    ],
};
