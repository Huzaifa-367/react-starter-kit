import React from 'react';
import { Head, useForm, Link, router } from '@inertiajs/react';
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
import { Tag, Plus, Eye, Trash2, EyeOff, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface CouponItem {
    id: number;
    code: string;
    stripe_coupon_id: string;
    discount_type: 'percent' | 'amount';
    discount_value: number;
    duration: 'once' | 'repeating' | 'forever';
    duration_in_months: number | null;
    max_redemptions: number | null;
    valid_until: string | null;
    is_active: boolean;
}

interface Props {
    coupons: {
        data: CouponItem[];
        links: any[];
        total: number;
        current_page: number;
        last_page: number;
    };
}

export default function CouponsIndex({ coupons }: Props) {
    const form = useForm({
        code: '',
        discount_type: 'percent' as 'percent' | 'amount',
        discount_value: 0,
        duration: 'once' as 'once' | 'repeating' | 'forever',
        duration_in_months: '',
        max_redemptions: '',
        valid_until: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/admin/coupons', {
            onSuccess: () => {
                toast.success('Coupon created and synced with Stripe.');
                form.reset();
            },
            onError: (err) => {
                toast.error(Object.values(err)[0] as string || 'Failed to create coupon.');
            },
        });
    };

    const handleToggle = (id: number) => {
        router.post(`/admin/coupons/${id}/toggle`, {}, {
            onSuccess: () => toast.success('Coupon state toggled.'),
        });
    };

    const handleDelete = (id: number) => {
        if (!confirm('Are you sure you want to delete this coupon? This will delete it on Stripe as well.')) return;
        router.delete(`/admin/coupons/${id}`, {
            onSuccess: () => toast.success('Coupon deleted.'),
        });
    };

    return (
        <>
            <Head title="Coupons Manager" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">Coupons</h1>
                        <p className="text-muted-foreground text-sm">
                            Configure marketing discount codes and Stripe promotion vouchers.
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                    {/* Create Form */}
                    <Card className="border border-border lg:col-span-1">
                        <CardHeader>
                            <CardTitle className="text-base font-bold flex items-center gap-2">
                                <Tag className="h-5 w-5 text-primary" />
                                Create Coupon
                            </CardTitle>
                        </CardHeader>
                        <form onSubmit={handleSubmit}>
                            <CardContent className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="code">Coupon Code (Unique name)</Label>
                                    <Input
                                        id="code"
                                        placeholder="e.g. SUMMER25"
                                        value={form.data.code}
                                        onChange={(e) => form.setData('code', e.target.value.toUpperCase())}
                                        required
                                        disabled={form.processing}
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="type">Type</Label>
                                        <select
                                            id="type"
                                            value={form.data.discount_type}
                                            onChange={(e) => form.setData('discount_type', e.target.value as any)}
                                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground"
                                        >
                                            <option value="percent">Percentage Off</option>
                                            <option value="amount">Amount Off</option>
                                        </select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="val">Value</Label>
                                        <Input
                                            id="val"
                                            type="number"
                                            step="0.01"
                                            value={form.data.discount_value}
                                            onChange={(e) => form.setData('discount_value', parseFloat(e.target.value))}
                                            required
                                            disabled={form.processing}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="duration">Duration</Label>
                                        <select
                                            id="duration"
                                            value={form.data.duration}
                                            onChange={(e) => form.setData('duration', e.target.value as any)}
                                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground"
                                        >
                                            <option value="once">Once</option>
                                            <option value="repeating">Repeating</option>
                                            <option value="forever">Forever</option>
                                        </select>
                                    </div>
                                    {form.data.duration === 'repeating' && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="months">Months Duration</Label>
                                            <Input
                                                id="months"
                                                type="number"
                                                value={form.data.duration_in_months}
                                                onChange={(e) => form.setData('duration_in_months', e.target.value)}
                                                required
                                            />
                                        </div>
                                    )}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="redemptions">Max Redemptions</Label>
                                        <Input
                                            id="redemptions"
                                            type="number"
                                            value={form.data.max_redemptions}
                                            onChange={(e) => form.setData('max_redemptions', e.target.value)}
                                            placeholder="Unlimited"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="valid">Valid Until</Label>
                                        <Input
                                            id="valid"
                                            type="date"
                                            value={form.data.valid_until || ''}
                                            onChange={(e) => form.setData('valid_until', e.target.value)}
                                        />
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter className="border-t border-border pt-4 pb-4">
                                <Button type="submit" disabled={form.processing} className="w-full cursor-pointer">
                                    {form.processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    Create & Sync Coupon
                                </Button>
                            </CardFooter>
                        </form>
                    </Card>

                    {/* Coupons Table */}
                    <Card className="border border-border lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-base font-bold">Coupon Code Directory</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse text-sm">
                                    <thead>
                                        <tr className="bg-muted/40 font-bold text-muted-foreground border-b border-border">
                                            <th className="p-4">Code</th>
                                            <th className="p-4">Value</th>
                                            <th className="p-4">Duration</th>
                                            <th className="p-4">Expiry</th>
                                            <th className="p-4">Status</th>
                                            <th className="p-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {coupons.data.length === 0 ? (
                                            <tr>
                                                <td colSpan={6} className="p-8 text-center text-muted-foreground">
                                                    No coupons found.
                                                </td>
                                            </tr>
                                        ) : (
                                            coupons.data.map((item) => (
                                                <tr key={item.id} className="hover:bg-muted/10 transition-colors">
                                                    <td className="p-4">
                                                        <div className="flex flex-col">
                                                            <Link href={`/admin/coupons/${item.id}`} className="font-semibold text-primary hover:underline">
                                                                {item.code}
                                                            </Link>
                                                            <span className="text-[10px] text-muted-foreground font-mono">{item.stripe_coupon_id}</span>
                                                        </div>
                                                    </td>
                                                    <td className="p-4 font-semibold">
                                                        {item.discount_type === 'percent' ? `${item.discount_value}%` : `$${item.discount_value}`}
                                                    </td>
                                                    <td className="p-4 capitalize">{item.duration}</td>
                                                    <td className="p-4 text-muted-foreground">
                                                        {item.valid_until ? new Date(item.valid_until).toLocaleDateString() : 'Never'}
                                                    </td>
                                                    <td className="p-4">
                                                        <Badge variant="outline" className={item.is_active ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-neutral-500/10 text-neutral-500 border-neutral-500/20'}>
                                                            {item.is_active ? 'Active' : 'Inactive'}
                                                        </Badge>
                                                    </td>
                                                    <td className="p-4 text-right space-x-1 whitespace-nowrap">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => handleToggle(item.id)}
                                                            className="h-8 w-8 text-muted-foreground hover:text-foreground cursor-pointer"
                                                        >
                                                            {item.is_active ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => handleDelete(item.id)}
                                                            className="h-8 w-8 text-muted-foreground hover:text-rose-500 cursor-pointer"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

CouponsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Coupons',
            href: '/admin/coupons',
        },
    ],
};
