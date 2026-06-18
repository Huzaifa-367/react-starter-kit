import React from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Loader2, Phone, CheckCircle, AlertCircle } from 'lucide-react';
import { toast } from 'sonner';

export default function PhoneUpdate() {
    const { auth } = usePage<any>().props;
    const user = auth.user;

    const { data, setData, post, processing, errors } = useForm({
        phone_number: user.phone_number || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/profile/phone', {
            onSuccess: () => {
                toast.success('OTP sent! Please verify your phone number.');
            },
            onError: (err) => {
                toast.error(err.phone_number || 'Failed to initiate phone verification.');
            },
        });
    };

    return (
        <>
            <Head title="Phone Settings" />

            <div className="mx-auto max-w-2xl px-4 py-8 space-y-6">
                <Heading
                    title="Phone Number"
                    description="Update your phone number. You will receive an OTP code to verify."
                />

                <div className="rounded-xl border border-border p-6 bg-card space-y-6">
                    {/* Verification Status */}
                    <div className="flex items-center justify-between border-b border-border pb-4">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-primary/10 rounded-lg text-primary">
                                <Phone className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-foreground">Current Number</p>
                                <p className="text-xs text-muted-foreground">
                                    {user.phone_number ? user.phone_number : 'No phone number registered'}
                                </p>
                            </div>
                        </div>

                        {user.phone_number && (
                            <Badge
                                variant="outline"
                                className={`text-[10px] font-bold uppercase tracking-wider ${
                                    user.phone_verified_at
                                        ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
                                        : 'bg-amber-500/10 text-amber-500 border-amber-500/20'
                                }`}
                            >
                                {user.phone_verified_at ? (
                                    <span className="flex items-center gap-1">
                                        <CheckCircle className="h-3 w-3" /> Verified
                                    </span>
                                ) : (
                                    <span className="flex items-center gap-1">
                                        <AlertCircle className="h-3 w-3" /> Unverified
                                    </span>
                                )}
                            </Badge>
                        )}
                    </div>

                    {/* Form */}
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="phone_number">New Phone Number (E.164 Format)</Label>
                            <Input
                                id="phone_number"
                                type="text"
                                placeholder="+1234567890"
                                value={data.phone_number}
                                onChange={(e) => setData('phone_number', e.target.value)}
                                required
                                disabled={processing}
                                className="max-w-md"
                            />
                            <p className="text-xs text-muted-foreground">
                                Enter your phone number starting with the country code (e.g. +14155552671).
                            </p>
                            <InputError message={errors.phone_number} />
                        </div>

                        <div className="flex items-center gap-4 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                Send Verification Code
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}

PhoneUpdate.layout = {
    breadcrumbs: [
        {
            title: 'Phone Settings',
            href: '/profile/phone',
        },
    ],
};
