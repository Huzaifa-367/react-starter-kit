import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { OnboardingChecklist } from '@/components/onboarding-checklist';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    LayoutDashboard,
    CreditCard,
    User,
    Settings,
    CheckCircle,
    Building2,
    Plus,
} from 'lucide-react';

export default function Dashboard() {
    const { auth, onboarding } = usePage<any>().props;
    const user = auth.user;
    const sub = user.active_subscription;

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex-1 space-y-8 p-4 md:p-8 pt-6">
                {/* Top Banner */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h2 className="text-3xl font-extrabold tracking-tight text-foreground">
                            Welcome back, {user.name}!
                        </h2>
                        <p className="text-muted-foreground text-sm mt-1">
                            Here is an overview of your account status and active features.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link href="/settings/profile">
                            <Button variant="outline" size="sm">
                                <Settings className="mr-2 h-4 w-4" />
                                Settings
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Main Content Layout */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Left Column: KPI Cards and Main Content */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* KPI Grid */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            {/* Subscription Status Card */}
                            <Card className="border border-border">
                                <CardHeader className="flex flex-row items-center justify-between pb-2">
                                    <CardTitle className="text-sm font-bold uppercase tracking-wider text-muted-foreground">
                                        Subscription Plan
                                    </CardTitle>
                                    <CreditCard className="h-4 w-4 text-primary" />
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="text-2xl font-extrabold text-foreground">
                                        {sub ? sub.plan.name : 'No Active Plan'}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {sub ? (
                                            <Badge className="bg-emerald-500/10 text-emerald-500 border-emerald-500/20 hover:bg-emerald-500/15 text-[10px] font-bold py-0.5 px-2">
                                                {sub.status.toUpperCase()}
                                            </Badge>
                                        ) : (
                                            <Badge variant="destructive" className="text-[10px] font-bold py-0.5 px-2">
                                                INACTIVE
                                            </Badge>
                                        )}
                                        <span className="text-xs text-muted-foreground">
                                            {sub ? (
                                                sub.plan.billing_period === 'lifetime'
                                                    ? 'Lifetime Plan'
                                                    : `Renews: ${new Date(sub.ends_at || sub.trial_ends_at).toLocaleDateString()}`
                                            ) : 'Please choose a plan to start.'}
                                        </span>
                                    </div>
                                    <div className="pt-2">
                                        <Link href="/billing">
                                            <Button variant="outline" size="sm" className="w-full text-xs font-semibold">
                                                Manage Billing
                                            </Button>
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Profile Status Card */}
                            <Card className="border border-border">
                                <CardHeader className="flex flex-row items-center justify-between pb-2">
                                    <CardTitle className="text-sm font-bold uppercase tracking-wider text-muted-foreground">
                                        Account Security
                                    </CardTitle>
                                    <User className="h-4 w-4 text-primary" />
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="text-2xl font-extrabold text-foreground">
                                        {user.email_verified_at ? 'Email Verified' : 'Email Pending'}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {user.email_verified_at ? (
                                            <Badge className="bg-emerald-500/10 text-emerald-500 border-emerald-500/20 text-[10px] font-bold py-0.5 px-2">
                                                SECURE
                                            </Badge>
                                        ) : (
                                            <Badge variant="destructive" className="text-[10px] font-bold py-0.5 px-2">
                                                UNVERIFIED
                                            </Badge>
                                        )}
                                        <span className="text-xs text-muted-foreground">
                                            {user.phone_number ? 'Phone registered' : 'No phone registered'}
                                        </span>
                                    </div>
                                    <div className="pt-2">
                                        <Link href="/settings/security">
                                            <Button variant="outline" size="sm" className="w-full text-xs font-semibold">
                                                Security Center
                                            </Button>
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Sandbox / Projects Workspace Card */}
                        <Card className="border border-border">
                            <CardHeader>
                                <CardTitle className="text-lg font-bold">Your Workspace</CardTitle>
                                <CardDescription>
                                    Create new projects, deploy templates, and monitor active services.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="h-[250px] relative overflow-hidden rounded-xl border border-dashed border-border/70 flex flex-col items-center justify-center text-center p-6">
                                <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/10 dark:stroke-neutral-100/10 pointer-events-none" />
                                <div className="space-y-4 z-10">
                                    <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        <Building2 className="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-semibold text-foreground">No projects created yet</h3>
                                        <p className="text-xs text-muted-foreground max-w-sm mt-1">
                                            Get started by creating your first workspace project to unlock API access and deployments.
                                        </p>
                                    </div>
                                    <Button size="sm" className="bg-primary hover:bg-primary/90">
                                        <Plus className="mr-1.5 h-4 w-4" />
                                        New Project
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Column: Onboarding Checklist */}
                    {onboarding && (
                        <div className="space-y-6">
                            <h3 className="text-sm font-bold uppercase tracking-wider text-muted-foreground">
                                Setup Checklist
                            </h3>
                            <OnboardingChecklist />
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
    ],
};
