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
import { ArrowLeft, Tag, Calendar, Users, Eye, EyeOff, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface Coupon {
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
    created_at: string;
}

interface Props {
    coupon: Coupon;
}

export default function CouponShow({ coupon }: Props) {
    const form = useForm({
        is_active: coupon.is_active,
        valid_until: coupon.valid_until || '',
        max_redemptions: coupon.max_redemptions || '',
    });

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/admin/coupons/${coupon.id}`, {
            onSuccess: () => {
                toast.success('Coupon details updated.');
            },
        });
    };

    const handleToggle = () => {
        router.post(`/admin/coupons/${coupon.id}/toggle`, {}, {
            onSuccess: (page) => {
                const updated = page.props.coupon as Coupon;
                form.setData('is_active', updated.is_active);
                toast.success('Coupon status updated.');
            },
        });
    };

    return (
        <>
            <Head title={`Coupon: ${coupon.code}`} />

            <div className="flex-1 space-y-6 p-6 max-w-4xl mx-auto">
                <div className="flex items-center gap-3">
                    <Link href="/admin/coupons">
                        <Button variant="ghost" size="icon" className="h-8 w-8 cursor-pointer">
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <Tag className="h-5 w-5 text-primary" />
                            Coupon: {coupon.code}
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Inspect Stripe promotion details and adjust local availability configurations.
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {/* Details Column */}
                    <Card className="border border-border md:col-span-1">
                        <CardHeader>
                            <CardTitle className="text-sm font-bold">Stripe Overview</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 text-xs">
                            <div className="flex justify-between py-1 border-b border-border">
                                <span className="text-muted-foreground font-semibold">Stripe ID</span>
                                <span className="font-mono">{coupon.stripe_coupon_id}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-border">
                                <span className="text-muted-foreground font-semibold">Discount Type</span>
                                <span className="capitalize">{coupon.discount_type}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-border">
                                <span className="text-muted-foreground font-semibold">Value</span>
                                <span>{coupon.discount_type === 'percent' ? `${coupon.discount_value}%` : `$${coupon.discount_value}`}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-border">
                                <span className="text-muted-foreground font-semibold">Duration</span>
                                <span className="capitalize">{coupon.duration}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-border">
                                <span className="text-muted-foreground font-semibold">Created Date</span>
                                <span>{new Date(coupon.created_at).toLocaleDateString()}</span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Configuration Form Column */}
                    <Card className="border border-border md:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-sm font-bold">Local Status Settings</CardTitle>
                            <CardDescription>Note: Stripe parameters like discount amounts are immutable once created.</CardDescription>
                        </CardHeader>
                        <form onSubmit={handleUpdate}>
                            <CardContent className="space-y-4">
                                <div className="flex justify-between items-center bg-muted/40 p-3 rounded-xl border border-border">
                                    <div className="space-y-1">
                                        <p className="font-semibold text-sm">Coupon Availability</p>
                                        <p className="text-xs text-muted-foreground">Toggle local use of this coupon.</p>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={handleToggle}
                                        className="cursor-pointer"
                                    >
                                        {form.data.is_active ? (
                                            <>
                                                <EyeOff className="h-3.5 w-3.5 mr-1" /> Deactivate
                                            </>
                                        ) : (
                                            <>
                                                <Eye className="h-3.5 w-3.5 mr-1" /> Activate
                                            </>
                                        )}
                                    </Button>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="max-redemp">Maximum Redemptions</Label>
                                        <Input
                                            id="max-redemp"
                                            type="number"
                                            value={form.data.max_redemptions}
                                            onChange={(e) => form.setData('max_redemptions', e.target.value)}
                                            placeholder="e.g. 100"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="until">Valid Until</Label>
                                        <Input
                                            id="until"
                                            type="date"
                                            value={form.data.valid_until}
                                            onChange={(e) => form.setData('valid_until', e.target.value)}
                                        />
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter className="border-t border-border pt-4 pb-4">
                                <Button type="submit" disabled={form.processing} className="ml-auto cursor-pointer">
                                    {form.processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    Save Configurations
                                </Button>
                            </CardFooter>
                        </form>
                    </Card>
                </div>
            </div>
        </>
    );
}

CouponShow.layout = {
    breadcrumbs: [
        { title: 'Coupons', href: '/admin/coupons' },
        { title: 'Coupon Details', href: '#' },
    ],
};
