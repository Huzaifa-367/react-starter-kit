import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Subscription } from '@/types/models';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    CreditCard,
    Calendar,
    Settings,
    CheckCircle,
    XCircle,
    ArrowUpRight,
    Loader2,
    Download,
    FileText,
} from 'lucide-react';
import { apiPost } from '@/lib/api';
import { toast } from 'sonner';

interface UsageItem {
    feature_name: string;
    feature_slug: string;
    used: number;
    limit: number | 'unlimited';
    percentage: number;
}

interface InvoiceItem {
    id: string;
    number: string;
    amount_paid: string;
    status: string;
    created: string;
    pdf_url: string | null;
}

interface Props {
    subscription: Subscription;
    usages: UsageItem[];
    invoices: InvoiceItem[];
}

export default function BillingDashboard({ subscription, usages, invoices }: Props) {
    const [portalLoading, setPortalLoading] = useState(false);
    const [actionLoading, setActionLoading] = useState<string | null>(null);

    const handleStripePortal = async () => {
        setPortalLoading(true);
        try {
            const { data } = await apiPost<{ portal_url?: string }>('/billing/portal');
            if (data.portal_url) {
                window.location.href = data.portal_url;
            }
        } catch (error: any) {
            toast.error(error.response?.data?.error || 'Could not open billing portal.');
        } finally {
            setPortalLoading(false);
        }
    };

    const handleCancel = () => {
        if (!confirm('Are you sure you want to cancel auto-renewal for your subscription?')) return;
        setActionLoading('cancel');
        router.post(
            '/billing/cancel',
            {},
            {
                onSuccess: () => {
                    toast.success('Subscription scheduled to cancel at period end.');
                },
                onError: (err) => {
                    toast.error(err.error || 'Failed to cancel subscription.');
                },
                onFinish: () => setActionLoading(null),
            }
        );
    };

    const handleResume = () => {
        setActionLoading('resume');
        router.post(
            '/billing/resume',
            {},
            {
                onSuccess: () => {
                    toast.success('Subscription auto-renewal resumed successfully!');
                },
                onError: (err) => {
                    toast.error(err.error || 'Failed to resume subscription.');
                },
                onFinish: () => setActionLoading(null),
            }
        );
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'active':
            case 'trialing':
                return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
            case 'grace':
                return 'bg-amber-500/10 text-amber-500 border-amber-500/20';
            case 'canceled':
            case 'expired':
                return 'bg-rose-500/10 text-rose-500 border-rose-500/20';
            default:
                return 'bg-neutral-500/10 text-neutral-500 border-neutral-500/20';
        }
    };

    return (
        <>
            <Head title="Billing Dashboard" />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 space-y-8">
                {/* Header */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-foreground">
                            Billing & Subscription
                        </h1>
                        <p className="text-muted-foreground mt-1">
                            Manage your active subscription plans, payment methods, and invoice history.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link href="/pricing/subscribed">
                            <Button variant="outline" size="sm">
                                Change Plan
                                <ArrowUpRight className="ml-2 h-4 w-4" />
                            </Button>
                        </Link>
                        <Button
                            onClick={handleStripePortal}
                            disabled={portalLoading}
                            size="sm"
                            className="bg-primary text-primary-foreground hover:bg-primary/90"
                        >
                            {portalLoading ? (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <Settings className="mr-2 h-4 w-4" />
                            )}
                            Customer Portal
                        </Button>
                    </div>
                </div>

                {/* Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Subscription Details Card */}
                    <Card className="lg:col-span-2 border border-border flex flex-col justify-between">
                        <div>
                            <CardHeader className="pb-4">
                                <div className="flex items-center justify-between">
                                    <div className="space-y-1">
                                        <CardTitle className="text-lg font-bold flex items-center gap-2">
                                            <CreditCard className="h-5 w-5 text-primary" />
                                            Active Plan: {subscription.plan.name}
                                        </CardTitle>
                                        <CardDescription>
                                            Status & renewal cycle information.
                                        </CardDescription>
                                    </div>
                                    <Badge variant="outline" className={`${getStatusColor(subscription.status)} uppercase font-bold py-0.5 px-2.5 text-xs`}>
                                        {subscription.status}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Price */}
                                <div className="flex items-baseline">
                                    <span className="text-3xl font-extrabold tracking-tight text-foreground">
                                        {new Intl.NumberFormat('en-US', {
                                            style: 'currency',
                                            currency: subscription.plan.currency || 'USD',
                                        }).format(subscription.plan.price)}
                                    </span>
                                    <span className="ml-1 text-sm font-semibold text-muted-foreground">
                                        /{subscription.plan.billing_period === 'monthly' ? 'month' : subscription.plan.billing_period === 'yearly' ? 'year' : 'lifetime'}
                                    </span>
                                </div>

                                {/* Subscription status descriptors */}
                                <div className="space-y-3">
                                    {subscription.trial_ends_at && subscription.status === 'trialing' && (
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Calendar className="h-4 w-4 text-primary" />
                                            <span>
                                                Trial ends on:{' '}
                                                <span className="font-semibold text-foreground">
                                                    {new Date(subscription.trial_ends_at).toLocaleDateString()}
                                                </span>
                                            </span>
                                        </div>
                                    )}

                                    {subscription.auto_renew && (subscription.ends_at || subscription.trial_ends_at) && (
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <CheckCircle className="h-4 w-4 text-emerald-500" />
                                            <span>
                                                Next renewal date:{' '}
                                                <span className="font-semibold text-foreground">
                                                    {new Date(subscription.ends_at || subscription.trial_ends_at!).toLocaleDateString()}
                                                </span>
                                            </span>
                                        </div>
                                    )}

                                    {!subscription.auto_renew && subscription.ends_at && (
                                        <div className="rounded-xl border border-amber-200/50 bg-amber-500/5 p-4 flex gap-3 items-start dark:border-amber-900/50 dark:bg-amber-950/20">
                                            <XCircle className="h-5 w-5 text-amber-500 mt-0.5 shrink-0" />
                                            <div className="space-y-1">
                                                <h5 className="text-sm font-semibold text-amber-800 dark:text-amber-300">
                                                    Auto-Renewal Cancelled
                                                </h5>
                                                <p className="text-xs text-amber-700/80 dark:text-amber-400/80 leading-relaxed">
                                                    Your subscription remains valid. Access will terminate on{' '}
                                                    <span className="font-semibold">
                                                        {new Date(subscription.ends_at).toLocaleDateString()}
                                                    </span>
                                                    .
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </div>

                        <CardFooter className="border-t border-border pt-4 pb-4 bg-muted/20 flex flex-wrap justify-between items-center gap-3">
                            <span className="text-xs text-muted-foreground">
                                Manage billing cycles and Stripe details from customer portal.
                            </span>

                            {subscription.plan.price > 0 && (
                                <div>
                                    {subscription.auto_renew ? (
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={handleCancel}
                                            disabled={actionLoading === 'cancel'}
                                        >
                                            {actionLoading === 'cancel' && (
                                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                            )}
                                            Cancel Auto-Renew
                                        </Button>
                                    ) : (
                                        (subscription.status === 'active' || subscription.status === 'grace' || subscription.status === 'trialing') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={handleResume}
                                                disabled={actionLoading === 'resume'}
                                                className="border-emerald-200 hover:bg-emerald-50 dark:border-emerald-900/50 dark:hover:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 font-semibold"
                                            >
                                                {actionLoading === 'resume' && (
                                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                )}
                                                Resume Auto-Renew
                                            </Button>
                                        )
                                    )}
                                </div>
                            )}
                        </CardFooter>
                    </Card>

                    {/* Feature Usages Card */}
                    <Card className="border border-border">
                        <CardHeader>
                            <CardTitle className="text-lg font-bold">Feature Usage</CardTitle>
                            <CardDescription>
                                Track your consumption limits for the current cycle.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {usages.length === 0 ? (
                                <p className="text-sm text-muted-foreground text-center py-4">
                                    No features found on this plan.
                                </p>
                            ) : (
                                usages.map((item) => (
                                    <div key={item.feature_slug} className="space-y-2">
                                        <div className="flex justify-between items-center text-sm">
                                            <span className="font-semibold text-foreground">{item.feature_name}</span>
                                            <span className="text-muted-foreground">
                                                {item.used} / {item.limit === 'unlimited' ? 'Unlimited' : item.limit}
                                            </span>
                                        </div>
                                        <div className="relative h-2 w-full bg-neutral-200 dark:bg-neutral-800 rounded-full overflow-hidden">
                                            <div
                                                className={`h-full rounded-full transition-all duration-500 ease-out ${
                                                    item.percentage >= 90
                                                        ? 'bg-rose-500'
                                                        : item.percentage >= 70
                                                        ? 'bg-amber-500'
                                                        : 'bg-primary'
                                                }`}
                                                style={{ width: `${item.limit === 'unlimited' ? 0 : item.percentage}%` }}
                                            />
                                        </div>
                                        {item.limit !== 'unlimited' && item.percentage >= 90 && (
                                            <p className="text-[10px] text-rose-500 font-semibold flex items-center gap-1">
                                                Running low! Consider upgrading soon.
                                            </p>
                                        )}
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Invoices List */}
                <Card className="border border-border">
                    <CardHeader>
                        <CardTitle className="text-lg font-bold">Invoice History</CardTitle>
                        <CardDescription>
                            Review your billing receipts and invoice downloads.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {invoices.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-8 text-center">
                                <FileText className="h-10 w-10 text-muted-foreground/50 mb-3" />
                                <p className="text-sm text-muted-foreground">No payment history or invoices found.</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto rounded-lg border border-border">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-muted/40 text-xs font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                                            <th className="p-4">Date</th>
                                            <th className="p-4">Invoice Number</th>
                                            <th className="p-4">Amount Paid</th>
                                            <th className="p-4">Status</th>
                                            <th className="p-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border text-sm">
                                        {invoices.map((inv) => (
                                            <tr key={inv.id} className="hover:bg-muted/10 transition-colors">
                                                <td className="p-4 text-muted-foreground">{inv.created}</td>
                                                <td className="p-4 font-mono font-medium text-foreground">{inv.number}</td>
                                                <td className="p-4 font-semibold text-foreground">{inv.amount_paid}</td>
                                                <td className="p-4">
                                                    <Badge
                                                        variant="outline"
                                                        className={`text-[10px] font-bold uppercase ${
                                                            inv.status === 'paid'
                                                                ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
                                                                : 'bg-amber-500/10 text-amber-500 border-amber-500/20'
                                                        }`}
                                                    >
                                                        {inv.status}
                                                    </Badge>
                                                </td>
                                                <td className="p-4 text-right">
                                                    {inv.pdf_url ? (
                                                        <a
                                                            href={inv.pdf_url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="inline-flex items-center gap-1.5 text-xs text-primary font-medium hover:underline cursor-pointer"
                                                        >
                                                            <Download className="h-3.5 w-3.5" />
                                                            Download Stripe
                                                        </a>
                                                    ) : (
                                                        <a
                                                            href={`/billing/invoices/${inv.id}/download`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="inline-flex items-center gap-1.5 text-xs text-primary font-medium hover:underline cursor-pointer"
                                                        >
                                                            <Download className="h-3.5 w-3.5" />
                                                            Download PDF
                                                        </a>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

BillingDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Billing',
            href: '/billing',
        },
    ],
};
