import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Plan, Subscription } from '@/types/models';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Check, X, Loader2, Sparkles, AlertCircle } from 'lucide-react';
import { apiPost } from '@/lib/api';
import { toast } from 'sonner';

interface Props {
    plans: Plan[];
    activeSubscription: Subscription | null;
}

export default function Pricing({ plans, activeSubscription }: Props) {
    const { auth } = usePage<any>().props;
    const [billingCycle, setBillingCycle] = useState<'monthly' | 'yearly'>('monthly');
    const [prorationOpen, setProrationOpen] = useState(false);
    const [prorationLoading, setProrationLoading] = useState(false);
    const [prorationDetails, setProrationDetails] = useState<{
        credit_applied: string;
        new_charge: string;
        total_due_today: string;
        next_billing_date: string;
    } | null>(null);
    const [selectedPlan, setSelectedPlan] = useState<Plan | null>(null);
    const [checkoutLoading, setCheckoutLoading] = useState<number | null>(null);

    // Filter plans to show depending on cycle:
    // Monthly cycle: show Free Starter, Pro Monthly, Enterprise Monthly, Lifetime
    // Yearly cycle: show Free Starter, Pro Yearly, Enterprise Yearly, Lifetime
    const filteredPlans = plans.filter((plan) => {
        if (plan.slug === 'free-starter' || plan.slug === 'lifetime') {
            return true;
        }
        if (billingCycle === 'monthly') {
            return plan.billing_period === 'monthly';
        } else {
            return plan.billing_period === 'yearly';
        }
    });

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
        }).format(price);
    };

    const handlePlanSelect = async (plan: Plan) => {
        // If current plan, do nothing
        if (activeSubscription && activeSubscription.plan.id === plan.id) {
            return;
        }

        // If guest, redirect to registration or login
        if (!activeSubscription && !auth?.user) {
            toast.error('Please log in or register to choose a plan.');
            router.visit('/login');
            return;
        }

        // If user has an active subscription and wants to switch
        // BUT if they are on a Free plan and moving to a paid plan, they must go through Stripe Checkout
        const isFreeToPaid = activeSubscription && Number(activeSubscription.plan.price) === 0 && Number(plan.price) > 0;

        if (activeSubscription && !isFreeToPaid) {
            setSelectedPlan(plan);
            setProrationOpen(true);
            setProrationLoading(true);
            setProrationDetails(null);

            try {
                const { data } = await apiPost<{
                    credit_applied: string;
                    new_charge: string;
                    total_due_today: string;
                    next_billing_date: string;
                }>('/billing/proration-preview', {
                    plan_id: plan.id,
                    billing_cycle: plan.billing_period === 'yearly' ? 'yearly' : 'monthly',
                });
                setProrationDetails(data);
            } catch (error: any) {
                console.error(error);
                toast.error(error.response?.data?.error || 'Failed to fetch proration details.');
                setProrationOpen(false);
            } finally {
                setProrationLoading(false);
            }
            return;
        }

        // No active subscription: Create Stripe Checkout Session
        setCheckoutLoading(plan.id);
        try {
            const { data } = await apiPost<{ checkout_url?: string; redirect?: string }>('/billing/checkout', {
                plan_id: plan.id,
                billing_cycle: plan.billing_period === 'yearly' ? 'yearly' : 'monthly',
            });

            if (data.checkout_url) {
                window.location.href = data.checkout_url;
            } else if (data.redirect) {
                router.visit(data.redirect);
            }
        } catch (error: any) {
            toast.error(error.response?.data?.error || 'Checkout session failed.');
        } finally {
            setCheckoutLoading(null);
        }
    };

    const confirmChangePlan = () => {
        if (!selectedPlan) return;

        setProrationLoading(true);
        router.post(
            '/billing/change-plan',
            { plan_id: selectedPlan.id },
            {
                onSuccess: () => {
                    toast.success('Your plan has been updated successfully!');
                    setProrationOpen(false);
                },
                onError: (errors) => {
                    toast.error(errors.error || 'Failed to change plan.');
                },
                onFinish: () => {
                    setProrationLoading(false);
                },
            }
        );
    };

    const renderFeatureValue = (feature: any) => {
        const val = feature.pivot?.value || feature.default_value;
        if (feature.slug === 'premium_support') {
            return val === 'true' ? (
                <span className="text-emerald-600 dark:text-emerald-400 font-medium">Included</span>
            ) : (
                <span className="text-muted-foreground">Not included</span>
            );
        }
        if (val === 'unlimited') {
            return <span className="font-semibold text-emerald-600 dark:text-emerald-400">Unlimited</span>;
        }
        return <span className="font-semibold text-foreground">{val}</span>;
    };

    return (
        <>
            <Head title="Pricing Plans" />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {/* Hero Section */}
                <div className="text-center space-y-4 mb-12">
                    <Badge variant="outline" className="px-3 py-1 border-primary/30 text-primary text-xs uppercase tracking-wider font-semibold">
                        Pricing
                    </Badge>
                    <h1 className="text-4xl font-extrabold tracking-tight text-foreground sm:text-5xl">
                        Simple, transparent pricing
                    </h1>
                    <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
                        Choose the subscription plan that fits your business needs. Save up to 20% on annual billing cycles.
                    </p>

                    {/* Billing Cycle Toggle */}
                    <div className="mt-8 flex justify-center">
                        <div className="relative flex rounded-full bg-muted p-1 border border-neutral-200 dark:border-neutral-800">
                            <button
                                type="button"
                                onClick={() => setBillingCycle('monthly')}
                                className={`rounded-full px-5 py-2 text-sm font-medium transition-all duration-300 ${
                                    billingCycle === 'monthly'
                                        ? 'bg-background text-foreground shadow-xs'
                                        : 'text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                Monthly Billing
                            </button>
                            <button
                                type="button"
                                onClick={() => setBillingCycle('yearly')}
                                className={`rounded-full px-5 py-2 text-sm font-medium transition-all duration-300 ${
                                    billingCycle === 'yearly'
                                        ? 'bg-background text-foreground shadow-xs'
                                        : 'text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                Yearly Billing
                                <Badge variant="secondary" className="ml-2 bg-primary/15 text-primary text-[10px] py-0.5 px-1.5 border border-primary/20">
                                    Save 20%
                                </Badge>
                            </button>
                        </div>
                    </div>
                </div>

                {/* Plan Grid */}
                <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4 items-stretch">
                    {filteredPlans.map((plan) => {
                        const isCurrent = activeSubscription && activeSubscription.plan.id === plan.id;
                        const isPro = plan.slug.startsWith('pro');
                        const isEnterprise = plan.slug.startsWith('enterprise');
                        const isLifetime = plan.slug === 'lifetime';

                        return (
                            <Card
                                key={plan.id}
                                className={`flex flex-col relative overflow-hidden transition-all duration-300 hover:shadow-lg ${
                                    isPro
                                        ? 'border-2 border-primary ring-2 ring-primary/10 shadow-md'
                                        : 'border border-border'
                                }`}
                            >
                                {isPro && (
                                    <div className="absolute top-0 right-0 bg-primary text-primary-foreground text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded-bl-lg flex items-center gap-1 shadow-sm">
                                        <Sparkles className="h-3 w-3" />
                                        Popular
                                    </div>
                                )}

                                <CardHeader className="pb-6">
                                    <CardTitle className="text-xl font-bold flex items-center justify-between">
                                        {plan.name}
                                        {isLifetime && <Badge className="bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500/20 border border-emerald-500/20">One-Time</Badge>}
                                    </CardTitle>
                                    <CardDescription className="min-h-[40px] mt-2">
                                        {plan.slug === 'free-starter' && 'Perfect for exploring and test-driving features.'}
                                        {isPro && 'Advanced power and priority tools for creators and developers.'}
                                        {isEnterprise && 'Scale with unlimited data and massive concurrency.'}
                                        {isLifetime && 'Pay once and unlock premium forever. No recurring charges.'}
                                    </CardDescription>
                                </CardHeader>

                                <CardContent className="flex-1 pb-6 space-y-6">
                                    {/* Price section */}
                                    <div className="flex items-baseline text-foreground">
                                        <span className="text-4xl font-extrabold tracking-tight">
                                            {formatPrice(plan.price)}
                                        </span>
                                        <span className="ml-1 text-sm font-semibold text-muted-foreground">
                                            {plan.billing_period === 'monthly' && '/mo'}
                                            {plan.billing_period === 'yearly' && '/yr'}
                                            {plan.billing_period === 'lifetime' && ' lifetime'}
                                        </span>
                                    </div>

                                    {/* Trial indicator */}
                                    {plan.trial_days > 0 && !isCurrent && (
                                        <div className="rounded-lg bg-primary/5 border border-primary/10 p-2.5 text-center text-xs text-primary font-medium">
                                            Includes {plan.trial_days}-day free trial period
                                        </div>
                                    )}

                                    {/* Divider */}
                                    <div className="h-px bg-border w-full" />

                                    {/* Features Checklist */}
                                    <div className="space-y-4">
                                        <h4 className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                            Plan Features
                                        </h4>
                                        <ul className="space-y-3">
                                            {plan.features.map((feat) => {
                                                const isValTrue = feat.pivot?.value === 'true';
                                                const isValFalse = feat.pivot?.value === 'false';

                                                return (
                                                    <li key={feat.id} className="flex items-start text-sm">
                                                        <div className="mr-3 mt-0.5">
                                                            {isValFalse ? (
                                                                <X className="h-4 w-4 text-rose-500" />
                                                            ) : (
                                                                <Check className="h-4 w-4 text-emerald-500" />
                                                            )}
                                                        </div>
                                                        <div className="flex-1 flex justify-between">
                                                            <span className="text-muted-foreground mr-2">{feat.name}</span>
                                                            <span className="text-right">{renderFeatureValue(feat)}</span>
                                                        </div>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    </div>
                                </CardContent>

                                <CardFooter className="pt-6 border-t border-border">
                                    <Button
                                        onClick={() => handlePlanSelect(plan)}
                                        disabled={isCurrent || checkoutLoading === plan.id}
                                        className={`w-full ${
                                            isCurrent
                                                ? 'bg-neutral-100 hover:bg-neutral-100 text-neutral-400 dark:bg-neutral-900 dark:text-neutral-600 dark:hover:bg-neutral-900 border border-neutral-200 dark:border-neutral-800'
                                                : isPro
                                                ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                                : 'variant-outline border border-input bg-background hover:bg-accent hover:text-accent-foreground'
                                        }`}
                                    >
                                        {checkoutLoading === plan.id && (
                                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                        )}
                                        {isCurrent ? 'Current Plan' : plan.price === 0 ? 'Get Started' : 'Subscribe Now'}
                                    </Button>
                                </CardFooter>
                            </Card>
                        );
                    })}
                </div>

                {/* Proration / Change Plan Modal */}
                <Dialog open={prorationOpen} onOpenChange={setProrationOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <AlertCircle className="h-5 w-5 text-primary" />
                                Review Subscription Change
                            </DialogTitle>
                            <DialogDescription>
                                Upgrading or downgrading will apply proration credits based on your unused time.
                            </DialogDescription>
                        </DialogHeader>

                        {prorationLoading ? (
                            <div className="flex flex-col items-center justify-center py-8 space-y-4">
                                <Loader2 className="h-8 w-8 animate-spin text-primary" />
                                <p className="text-sm text-muted-foreground">Calculating price adjustments...</p>
                            </div>
                        ) : (
                            prorationDetails && (
                                <div className="space-y-4 py-2">
                                    <div className="rounded-xl border border-border bg-muted/30 p-4 space-y-3">
                                        <div className="flex justify-between text-sm">
                                            <span className="text-muted-foreground">Target Plan</span>
                                            <span className="font-semibold text-foreground">{selectedPlan?.name}</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-muted-foreground">Unused Credit Applied</span>
                                            <span className="font-semibold text-emerald-600 dark:text-emerald-400">
                                                -{prorationDetails.credit_applied}
                                            </span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-muted-foreground">New Plan Rate</span>
                                            <span className="font-semibold text-foreground">{prorationDetails.new_charge}</span>
                                        </div>
                                        <div className="h-px bg-border" />
                                        <div className="flex justify-between text-base font-bold">
                                            <span>Total Due Today</span>
                                            <span className="text-primary">{prorationDetails.total_due_today}</span>
                                        </div>
                                    </div>

                                    <div className="text-xs text-muted-foreground leading-relaxed">
                                        Your next billing cycle will renew on{' '}
                                        <span className="font-semibold text-foreground">
                                            {prorationDetails.next_billing_date}
                                        </span>{' '}
                                        for the standard rate.
                                    </div>
                                </div>
                            )
                        )}

                        <DialogFooter className="gap-2 sm:gap-0">
                            <Button
                                variant="outline"
                                onClick={() => setProrationOpen(false)}
                                disabled={prorationLoading}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={confirmChangePlan}
                                disabled={prorationLoading}
                                className="bg-primary text-primary-foreground hover:bg-primary/90"
                            >
                                {prorationLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                Confirm Change
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

Pricing.layout = {
    breadcrumbs: [
        {
            title: 'Pricing Plans',
            href: '/pricing',
        },
    ],
};
