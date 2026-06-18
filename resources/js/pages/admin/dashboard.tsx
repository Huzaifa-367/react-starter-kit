import React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    AlertCircle,
    ArrowRight,
    CreditCard,
    HeartPulse,
    Sliders,
    TrendingUp,
    UserPlus,
    Users,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface Props {
    stats?: {
        total_users: number;
        active_subscriptions: number;
        mrr: number;
        churn_rate: number;
    };
    recent_users?: Array<{
        id: number;
        name: string;
        email: string;
        created_at: string;
    }>;
    recent_activities?: Array<{
        id: number;
        description: string;
        user_name: string;
        created_at: string;
    }>;
}

export default function AdminDashboard({ stats, recent_users, recent_activities }: Props) {
    // Fallbacks if stats are empty/mocked
    const finalStats = stats || {
        total_users: 1250,
        active_subscriptions: 450,
        mrr: 8990.0,
        churn_rate: 2.4,
    };

    const finalUsers = recent_users || [
        { id: 1, name: 'Alice Smith', email: 'alice@example.com', created_at: '2026-06-18' },
        { id: 2, name: 'Bob Johnson', email: 'bob@example.com', created_at: '2026-06-18' },
        { id: 3, name: 'Charlie Brown', email: 'charlie@example.com', created_at: '2026-06-17' },
    ];

    const finalActivities = recent_activities || [
        { id: 1, description: 'User subscribed to Pro Monthly', user_name: 'Alice Smith', created_at: '2026-06-18 20:30:15' },
        { id: 2, description: 'User created a new project', user_name: 'Bob Johnson', created_at: '2026-06-18 19:45:00' },
        { id: 3, description: 'Stripe payment succeeded', user_name: 'System', created_at: '2026-06-18 19:00:22' },
    ];

    const formatCurrency = (val: number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(val);
    };

    return (
        <>
            <Head title="Admin Dashboard" />

            <div className="flex-1 space-y-8 p-6 md:p-8 pt-6">
                {/* Header */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h2 className="text-3xl font-extrabold tracking-tight text-foreground">
                            Admin Dashboard
                        </h2>
                        <p className="text-muted-foreground text-sm mt-1">
                            SaaS platform operational metrics and user activity overview.
                        </p>
                    </div>
                </div>

                {/* KPI metrics cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    {/* Total Users */}
                    <Card className="border border-border">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                Total Customers
                            </CardTitle>
                            <Users className="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-extrabold">{finalStats.total_users}</div>
                            <p className="text-[10px] text-emerald-500 font-semibold mt-1">
                                +12% from last month
                            </p>
                        </CardContent>
                    </Card>

                    {/* Active Subscriptions */}
                    <Card className="border border-border">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                Active Subscriptions
                            </CardTitle>
                            <CreditCard className="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-extrabold">{finalStats.active_subscriptions}</div>
                            <p className="text-[10px] text-emerald-500 font-semibold mt-1">
                                85% conversion rate
                            </p>
                        </CardContent>
                    </Card>

                    {/* MRR */}
                    <Card className="border border-border">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                Monthly Recurring Revenue
                            </CardTitle>
                            <TrendingUp className="h-4 w-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-extrabold">{formatCurrency(finalStats.mrr)}</div>
                            <p className="text-[10px] text-emerald-500 font-semibold mt-1">
                                +8.2% ARR projection
                            </p>
                        </CardContent>
                    </Card>

                    {/* Churn Rate */}
                    <Card className="border border-border">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                Platform Churn Rate
                            </CardTitle>
                            <AlertCircle className="h-4 w-4 text-rose-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-extrabold">{finalStats.churn_rate}%</div>
                            <p className="text-[10px] text-emerald-500 font-semibold mt-1">
                                -0.3% improvement
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Main section: Activities and Quick Links */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Recent Activities */}
                    <Card className="lg:col-span-2 border border-border">
                        <CardHeader>
                            <CardTitle className="text-lg font-bold">Recent System Logs</CardTitle>
                            <CardDescription>Live operational events and user actions.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="divide-y divide-border">
                                {finalActivities.map((act) => (
                                    <div key={act.id} className="flex items-start gap-4 py-3 first:pt-0 last:pb-0">
                                        <div className="p-2 bg-muted rounded-lg mt-0.5">
                                            <Activity className="h-4 w-4 text-primary" />
                                        </div>
                                        <div className="flex-1 flex justify-between gap-4">
                                            <div>
                                                <p className="text-sm font-semibold text-foreground">
                                                    {act.description}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    By <span className="font-semibold">{act.user_name}</span>
                                                </p>
                                            </div>
                                            <span className="text-[10px] font-mono text-muted-foreground shrink-0 mt-0.5">
                                                {act.created_at}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Quick Administrative Shortcuts */}
                    <div className="space-y-6">
                        <Card className="border border-border">
                            <CardHeader>
                                <CardTitle className="text-lg font-bold">Quick Actions</CardTitle>
                                <CardDescription>Direct navigation to config tools.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <Link href="/admin/users" className="block">
                                    <Button variant="outline" className="w-full justify-between cursor-pointer">
                                        <span className="flex items-center gap-2">
                                            <Users className="h-4 w-4" /> Users Manager
                                        </span>
                                        <ArrowRight className="h-4 w-4" />
                                    </Button>
                                </Link>

                                <Link href="/admin/settings" className="block">
                                    <Button variant="outline" className="w-full justify-between cursor-pointer">
                                        <span className="flex items-center gap-2">
                                            <Sliders className="h-4 w-4" /> System Settings
                                        </span>
                                        <ArrowRight className="h-4 w-4" />
                                    </Button>
                                </Link>

                                <Link href="/admin/system-health" className="block">
                                    <Button variant="outline" className="w-full justify-between cursor-pointer">
                                        <span className="flex items-center gap-2">
                                            <HeartPulse className="h-4 w-4" /> Platform Diagnostics
                                        </span>
                                        <ArrowRight className="h-4 w-4" />
                                    </Button>
                                </Link>
                            </CardContent>
                        </Card>

                        {/* Recent User Signups */}
                        <Card className="border border-border">
                            <CardHeader>
                                <CardTitle className="text-base font-bold">New Customers</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-3">
                                    {finalUsers.map((u) => (
                                        <div key={u.id} className="flex justify-between items-center gap-2">
                                            <div className="flex items-center gap-2">
                                                <div className="p-1.5 bg-primary/10 rounded-lg text-primary">
                                                    <UserPlus className="h-3.5 w-3.5" />
                                                </div>
                                                <div>
                                                    <p className="text-xs font-semibold text-foreground">{u.name}</p>
                                                    <p className="text-[10px] text-muted-foreground font-mono">{u.email}</p>
                                                </div>
                                            </div>
                                            <Badge variant="outline" className="text-[9px] py-0 px-1 text-muted-foreground border-border font-normal">
                                                {u.created_at}
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Admin Dashboard',
            href: '/admin/dashboard',
        },
    ],
};
