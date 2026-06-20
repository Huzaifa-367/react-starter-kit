import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
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
import {
    Plus,
    Edit2,
    Sliders,
    Eye,
    EyeOff,
    Trash2,
    Loader2,
    DollarSign,
    Sparkles,
} from 'lucide-react';
import { toast } from 'sonner';

interface FeatureItem {
    id: number;
    name: string;
    slug: string;
}

interface PlanItem {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    currency: string;
    billing_period: 'monthly' | 'yearly' | 'lifetime';
    trial_days: number;
    grace_days: number;
    sort_order: number;
    is_active: boolean;
    stripe_product_id: string | null;
    stripe_price_id: string | null;
    features: Array<{
        id: number;
        name: string;
        slug: string;
        pivot: { value: string };
    }>;
    subscriptions_count: number;
}

interface Props {
    plans: PlanItem[];
    features: FeatureItem[];
}

export default function PlansIndex({ plans, features }: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editOpen, setEditOpen] = useState(false);
    const [featuresOpen, setFeaturesOpen] = useState(false);
    const [activePlan, setActivePlan] = useState<PlanItem | null>(null);
    const [actionLoading, setActionLoading] = useState<string | null>(null);

    const planForm = useForm({
        id: undefined as number | undefined,
        name: '',
        slug: '',
        description: '',
        price: 0,
        currency: 'USD',
        billing_period: 'monthly',
        trial_days: 0,
        grace_days: 0,
        sort_order: 0,
        is_active: true,
        stripe_product_id: '',
        stripe_price_id: '',
    });

    const featuresForm = useForm({
        features: {} as Record<number, string>,
    });

    const handleOpenCreate = () => {
        planForm.reset();
        planForm.setData({
            id: undefined,
            name: '',
            slug: '',
            description: '',
            price: 0,
            currency: 'USD',
            billing_period: 'monthly',
            trial_days: 0,
            grace_days: 0,
            sort_order: plans.length + 1,
            is_active: true,
            stripe_product_id: '',
            stripe_price_id: '',
        });
        setCreateOpen(true);
    };

    const handleOpenEdit = (plan: PlanItem) => {
        planForm.reset();
        planForm.setData({
            id: plan.id,
            name: plan.name,
            slug: plan.slug,
            description: plan.description || '',
            price: plan.price,
            currency: plan.currency,
            billing_period: plan.billing_period,
            trial_days: plan.trial_days,
            grace_days: plan.grace_days,
            sort_order: plan.sort_order,
            is_active: plan.is_active,
            stripe_product_id: plan.stripe_product_id || '',
            stripe_price_id: plan.stripe_price_id || '',
        });
        setEditOpen(true);
    };

    const handleOpenFeatures = (plan: PlanItem) => {
        setActivePlan(plan);
        const currentVals: Record<number, string> = {};
        features.forEach((f) => {
            const planFeature = plan.features.find((pf) => pf.id === f.id);
            currentVals[f.id] = planFeature ? planFeature.pivot.value : '';
        });
        featuresForm.setData('features', currentVals);
        setFeaturesOpen(true);
    };

    const handleSavePlan = (e: React.FormEvent) => {
        e.preventDefault();
        const isEdit = !!planForm.data.id;
        const endpoint = isEdit ? `/admin/plans/${planForm.data.id}` : '/admin/plans';
        const method = isEdit ? 'put' : 'post';

        planForm.submit(method, endpoint, {
            onSuccess: () => {
                toast.success(isEdit ? 'Plan updated successfully.' : 'Plan created.');
                setCreateOpen(false);
                setEditOpen(false);
            },
            onError: (err) => {
                toast.error(Object.values(err)[0] as string || 'An error occurred.');
            },
        });
    };

    const handleSaveFeatures = (e: React.FormEvent) => {
        e.preventDefault();
        if (!activePlan) return;

        featuresForm.post(`/admin/plans/${activePlan.id}/features`, {
            onSuccess: () => {
                toast.success('Features updated.');
                setFeaturesOpen(false);
            },
            onError: () => toast.error('Failed to sync features.'),
        });
    };

    const handleToggleActive = (plan: PlanItem) => {
        setActionLoading(`toggle-${plan.id}`);
        router.post(`/admin/plans/${plan.id}/toggle`, {}, {
            onSuccess: () => toast.success('Plan status toggled.'),
            onFinish: () => setActionLoading(null),
        });
    };

    const handleDeletePlan = (plan: PlanItem) => {
        if (plan.subscriptions_count > 0) {
            toast.error('Cannot delete plans with active subscribers.');
            return;
        }
        if (!confirm(`Are you sure you want to delete the plan "${plan.name}"?`)) return;

        setActionLoading(`delete-${plan.id}`);
        router.delete(`/admin/plans/${plan.id}`, {
            onSuccess: () => toast.success('Plan deleted successfully.'),
            onFinish: () => setActionLoading(null),
        });
    };

    return (
        <>
            <Head title="Plans & Features" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">SaaS Plans</h1>
                        <p className="text-muted-foreground text-sm">
                            Configure your pricing tiers, Stripe product pricing, and feature limit mappings.
                        </p>
                    </div>
                    <Button onClick={handleOpenCreate} className="cursor-pointer">
                        <Plus className="mr-2 h-4 w-4" />
                        Create Plan
                    </Button>
                </div>

                {/* Plans List Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {plans.map((plan) => (
                        <Card key={plan.id} className={`flex flex-col relative border ${plan.is_active ? 'border-border' : 'border-dashed border-neutral-300 dark:border-neutral-700 opacity-75'}`}>
                            <CardHeader className="pb-4">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <CardTitle className="text-lg font-bold flex items-center gap-2">
                                            {plan.name}
                                        </CardTitle>
                                        <Badge variant="secondary" className="text-[9px] py-0 px-1.5 uppercase font-bold mt-1">
                                            {plan.billing_period}
                                        </Badge>
                                    </div>

                                    <Badge
                                        variant="outline"
                                        className={`text-[10px] font-bold uppercase ${
                                            plan.is_active
                                                ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
                                                : 'bg-neutral-500/10 text-neutral-500 border-neutral-500/20'
                                        }`}
                                    >
                                        {plan.is_active ? 'Active' : 'Inactive'}
                                    </Badge>
                                </div>
                                <CardDescription className="min-h-[40px] text-xs mt-2">
                                    {plan.description || 'No description provided.'}
                                </CardDescription>
                            </CardHeader>

                            <CardContent className="flex-1 pb-4 space-y-4">
                                <div className="flex items-baseline text-foreground">
                                    <span className="text-3xl font-extrabold tracking-tight">
                                        {new Intl.NumberFormat('en-US', {
                                            style: 'currency',
                                            currency: plan.currency,
                                        }).format(plan.price)}
                                    </span>
                                    <span className="ml-1 text-xs text-muted-foreground">
                                        /{plan.billing_period === 'lifetime' ? 'one-time' : plan.billing_period === 'yearly' ? 'yr' : 'mo'}
                                    </span>
                                </div>

                                <div className="space-y-1 text-xs text-muted-foreground border-t border-border pt-3">
                                    <div className="flex justify-between">
                                        <span>Active Subscribers</span>
                                        <span className="font-semibold text-foreground">{plan.subscriptions_count} users</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Trial / Grace Days</span>
                                        <span className="font-semibold text-foreground">{plan.trial_days}d / {plan.grace_days}d</span>
                                    </div>
                                </div>
                            </CardContent>

                            <CardFooter className="border-t border-border pt-4 pb-4 bg-muted/20 flex justify-between items-center gap-2">
                                <div className="flex gap-1.5">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleOpenEdit(plan)}
                                        className="h-8 text-xs cursor-pointer"
                                    >
                                        <Edit2 className="h-3 w-3 mr-1" /> Edit
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleOpenFeatures(plan)}
                                        className="h-8 text-xs cursor-pointer"
                                    >
                                        <Sliders className="h-3 w-3 mr-1" /> Features
                                    </Button>
                                </div>

                                <div className="flex gap-1.5">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        disabled={actionLoading === `toggle-${plan.id}`}
                                        onClick={() => handleToggleActive(plan)}
                                        className="h-8 w-8 text-muted-foreground hover:text-foreground cursor-pointer"
                                    >
                                        {actionLoading === `toggle-${plan.id}` ? (
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                        ) : plan.is_active ? (
                                            <EyeOff className="h-4 w-4" />
                                        ) : (
                                            <Eye className="h-4 w-4" />
                                        )}
                                    </Button>

                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        disabled={plan.subscriptions_count > 0 || actionLoading === `delete-${plan.id}`}
                                        onClick={() => handleDeletePlan(plan)}
                                        className="h-8 w-8 text-muted-foreground hover:text-rose-500 cursor-pointer"
                                    >
                                        {actionLoading === `delete-${plan.id}` ? (
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                        ) : (
                                            <Trash2 className="h-4 w-4" />
                                        )}
                                    </Button>
                                </div>
                            </CardFooter>
                        </Card>
                    ))}
                </div>

                {/* Plan Create / Edit Modal */}
                <Dialog open={createOpen || editOpen} onOpenChange={(val) => {
                    if (!val) {
                        setCreateOpen(false);
                        setEditOpen(false);
                    }
                }}>
                    <DialogContent className="sm:max-w-lg overflow-y-auto max-h-[85vh]">
                        <DialogHeader>
                            <DialogTitle>{editOpen ? 'Edit Plan' : 'Create SaaS Plan'}</DialogTitle>
                            <DialogDescription>
                                Set price settings, description, and link Stripe product configurations.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleSavePlan} className="space-y-4 py-2">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="plan-name">Name</Label>
                                    <Input
                                        id="plan-name"
                                        placeholder="e.g. Pro Monthly"
                                        value={planForm.data.name}
                                        onChange={(e) => planForm.setData('name', e.target.value)}
                                        required
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="plan-slug">Slug</Label>
                                    <Input
                                        id="plan-slug"
                                        placeholder="e.g. pro-monthly"
                                        value={planForm.data.slug}
                                        onChange={(e) => planForm.setData('slug', e.target.value)}
                                        required
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="plan-desc">Description</Label>
                                <Input
                                    id="plan-desc"
                                    placeholder="Enter plan highlights..."
                                    value={planForm.data.description}
                                    onChange={(e) => planForm.setData('description', e.target.value)}
                                />
                            </div>

                            <div className="grid grid-cols-3 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="plan-price">Price</Label>
                                    <Input
                                        id="plan-price"
                                        type="number"
                                        step="0.01"
                                        value={planForm.data.price}
                                        onChange={(e) => planForm.setData('price', parseFloat(e.target.value))}
                                        required
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="plan-currency">Currency</Label>
                                    <Input
                                        id="plan-currency"
                                        value={planForm.data.currency}
                                        onChange={(e) => planForm.setData('currency', e.target.value)}
                                        required
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="plan-period">Period</Label>
                                    <select
                                        id="plan-period"
                                        value={planForm.data.billing_period}
                                        onChange={(e) => planForm.setData('billing_period', e.target.value as any)}
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring"
                                    >
                                        <option value="monthly">Monthly</option>
                                        <option value="yearly">Yearly</option>
                                        <option value="lifetime">Lifetime</option>
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-3 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="plan-trial">Trial Days</Label>
                                    <Input
                                        id="plan-trial"
                                        type="number"
                                        value={planForm.data.trial_days}
                                        onChange={(e) => planForm.setData('trial_days', parseInt(e.target.value))}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="plan-grace">Grace Days</Label>
                                    <Input
                                        id="plan-grace"
                                        type="number"
                                        value={planForm.data.grace_days}
                                        onChange={(e) => planForm.setData('grace_days', parseInt(e.target.value))}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="plan-sort">Sort Order</Label>
                                    <Input
                                        id="plan-sort"
                                        type="number"
                                        value={planForm.data.sort_order}
                                        onChange={(e) => planForm.setData('sort_order', parseInt(e.target.value))}
                                    />
                                </div>
                            </div>

                            <div className="space-y-3 border-t border-border pt-3">
                                <h4 className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                    Stripe Configuration (Optional)
                                </h4>
                                <div className="grid gap-2">
                                    <Label htmlFor="stripe-prod">Stripe Product ID</Label>
                                    <Input
                                        id="stripe-prod"
                                        placeholder="prod_..."
                                        value={planForm.data.stripe_product_id}
                                        onChange={(e) => planForm.setData('stripe_product_id', e.target.value)}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="stripe-price-id">Stripe Price ID</Label>
                                    <Input
                                        id="stripe-price-id"
                                        placeholder="price_..."
                                        value={planForm.data.stripe_price_id}
                                        onChange={(e) => planForm.setData('stripe_price_id', e.target.value)}
                                    />
                                </div>
                            </div>

                            <DialogFooter className="pt-2">
                                <Button type="button" variant="outline" onClick={() => {
                                    setCreateOpen(false);
                                    setEditOpen(false);
                                }}>Cancel</Button>
                                <Button type="submit" disabled={planForm.processing}>
                                    {planForm.processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    Save Plan
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Edit Plan Features Modal */}
                <Dialog open={featuresOpen} onOpenChange={setFeaturesOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Edit Plan Features</DialogTitle>
                            <DialogDescription>
                                Set limits or parameters for features associated with "{activePlan?.name}". Use "unlimited" or numeric values.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleSaveFeatures} className="space-y-4 py-2">
                            <div className="space-y-4 max-h-[50vh] overflow-y-auto pr-2">
                                {features.map((feat) => (
                                    <div key={feat.id} className="grid grid-cols-3 items-center gap-4 border-b border-border pb-2 last:border-0">
                                        <Label htmlFor={`feat-${feat.id}`} className="col-span-2 flex flex-col gap-0.5">
                                            <span className="font-semibold text-foreground">{feat.name}</span>
                                            <span className="text-[10px] text-muted-foreground font-mono">{feat.slug}</span>
                                        </Label>
                                        <Input
                                            id={`feat-${feat.id}`}
                                            value={featuresForm.data.features[feat.id] || ''}
                                            onChange={(e) => {
                                                const currentFeatures = { ...featuresForm.data.features };
                                                currentFeatures[feat.id] = e.target.value;
                                                featuresForm.setData('features', currentFeatures);
                                            }}
                                            placeholder="unlimited or number"
                                            className="text-right font-semibold"
                                        />
                                    </div>
                                ))}
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setFeaturesOpen(false)}>Cancel</Button>
                                <Button type="submit" disabled={featuresForm.processing}>
                                    {featuresForm.processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    Save Features
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

PlansIndex.layout = {
    breadcrumbs: [
        {
            title: 'SaaS Plans',
            href: '/admin/plans',
        },
    ],
};
