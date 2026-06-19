import React, { useState } from 'react';
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
import { Mail, Plus, X, Send, RotateCcw, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface InvitationItem {
    id: number;
    email: string;
    token: string;
    status: 'pending' | 'sent' | 'accepted' | 'expired' | 'cancelled';
    expires_at: string;
    message: string | null;
    inviter?: {
        name: string;
    };
    created_at: string;
}

interface Props {
    invitations: {
        data: InvitationItem[];
        links: any[];
        total: number;
        current_page: number;
        last_page: number;
    };
}

export default function InvitationsIndex({ invitations }: Props) {
    const [actionLoading, setActionLoading] = useState<number | null>(null);

    const form = useForm({
        email: '',
        phone_number: '',
        message: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/admin/invitations', {
            onSuccess: () => {
                toast.success('Invitation sent successfully.');
                form.reset();
            },
            onError: (err) => {
                toast.error(Object.values(err)[0] as string || 'Failed to send invitation.');
            },
        });
    };

    const handleResend = (id: number) => {
        setActionLoading(id);
        router.post(`/admin/invitations/${id}/resend`, {}, {
            onSuccess: () => toast.success('Invitation link resent.'),
            onFinish: () => setActionLoading(null),
        });
    };

    const handleCancel = (id: number) => {
        if (!confirm('Are you sure you want to cancel this invitation?')) return;
        setActionLoading(id);
        router.delete(`/admin/invitations/${id}`, {
            onSuccess: () => toast.success('Invitation cancelled.'),
            onFinish: () => setActionLoading(null),
        });
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'accepted':
                return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
            case 'pending':
            case 'sent':
                return 'bg-blue-500/10 text-blue-500 border-blue-500/20';
            case 'expired':
                return 'bg-amber-500/10 text-amber-500 border-amber-500/20';
            case 'cancelled':
            default:
                return 'bg-rose-500/10 text-rose-500 border-rose-500/20';
        }
    };

    return (
        <>
            <Head title="Invitations Manager" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">Invitations</h1>
                        <p className="text-muted-foreground text-sm">
                            Manage invite-only registration requests and track guest accounts.
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                    {/* Send Invitation Form */}
                    <Card className="border border-border lg:col-span-1">
                        <CardHeader>
                            <CardTitle className="text-base font-bold flex items-center gap-2">
                                <Mail className="h-5 w-5 text-primary" />
                                Invite Guest User
                            </CardTitle>
                            <CardDescription>
                                Dispatch a private sign-up token to a prospective customer.
                            </CardDescription>
                        </CardHeader>
                        <form onSubmit={handleSubmit}>
                            <CardContent className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email Address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        placeholder="guest@example.com"
                                        value={form.data.email}
                                        onChange={(e) => form.setData('email', e.target.value)}
                                        required
                                        disabled={form.processing}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Phone Number (Optional)</Label>
                                    <Input
                                        id="phone"
                                        type="text"
                                        placeholder="+1234567890"
                                        value={form.data.phone_number}
                                        onChange={(e) => form.setData('phone_number', e.target.value)}
                                        disabled={form.processing}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="message">Custom Message (Optional)</Label>
                                    <textarea
                                        id="message"
                                        placeholder="Add a personalized message..."
                                        value={form.data.message}
                                        onChange={(e) => form.setData('message', e.target.value)}
                                        disabled={form.processing}
                                        className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring"
                                    />
                                </div>
                            </CardContent>
                            <CardFooter className="border-t border-border pt-4 pb-4">
                                <Button type="submit" disabled={form.processing} className="w-full cursor-pointer">
                                    {form.processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    Send Invitation
                                </Button>
                            </CardFooter>
                        </form>
                    </Card>

                    {/* Sent Invitations List */}
                    <Card className="border border-border lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-base font-bold">Sent Tokens</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse text-sm">
                                    <thead>
                                        <tr className="bg-muted/40 font-bold text-muted-foreground border-b border-border">
                                            <th className="p-4">Email</th>
                                            <th className="p-4">Invited By</th>
                                            <th className="p-4">Expires</th>
                                            <th className="p-4">Status</th>
                                            <th className="p-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {invitations.data.length === 0 ? (
                                            <tr>
                                                <td colSpan={5} className="p-8 text-center text-muted-foreground">
                                                    No invitations sent yet.
                                                </td>
                                            </tr>
                                        ) : (
                                            invitations.data.map((item) => (
                                                <tr key={item.id} className="hover:bg-muted/10 transition-colors">
                                                    <td className="p-4 font-semibold text-foreground">{item.email}</td>
                                                    <td className="p-4 text-muted-foreground">{item.inviter?.name || 'System'}</td>
                                                    <td className="p-4 text-muted-foreground">
                                                        {new Date(item.expires_at).toLocaleDateString()}
                                                    </td>
                                                    <td className="p-4">
                                                        <Badge variant="outline" className={`${getStatusColor(item.status)} uppercase font-bold py-0.5 px-2 text-[10px]`}>
                                                            {item.status}
                                                        </Badge>
                                                    </td>
                                                    <td className="p-4 text-right space-x-1.5 whitespace-nowrap">
                                                        {item.status !== 'accepted' && item.status !== 'cancelled' && (
                                                            <>
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() => handleResend(item.id)}
                                                                    disabled={actionLoading === item.id}
                                                                    className="h-8 text-xs cursor-pointer"
                                                                >
                                                                    {actionLoading === item.id ? (
                                                                        <Loader2 className="h-3 w-3 animate-spin mr-1" />
                                                                    ) : (
                                                                        <RotateCcw className="h-3 w-3 mr-1" />
                                                                    )}
                                                                    Resend
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    onClick={() => handleCancel(item.id)}
                                                                    disabled={actionLoading === item.id}
                                                                    className="h-8 w-8 text-muted-foreground hover:text-rose-500 cursor-pointer"
                                                                >
                                                                    <X className="h-4 w-4" />
                                                                </Button>
                                                            </>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {invitations.last_page > 1 && (
                                <div className="flex items-center justify-between border-t border-border px-4 py-3 sm:px-6">
                                    <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                        <p className="text-sm text-muted-foreground">
                                            Showing page <span className="font-semibold text-foreground">{invitations.current_page}</span> of{' '}
                                            <span className="font-semibold text-foreground">{invitations.last_page}</span> ({invitations.total} total tokens)
                                        </p>
                                        <nav className="isolate inline-flex -space-x-px rounded-md shadow-xs" aria-label="Pagination">
                                            {invitations.links.map((link: any, idx: number) => (
                                                <Link
                                                    key={idx}
                                                    href={link.url || '#'}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                    className={`relative inline-flex items-center px-4 py-2 text-sm font-semibold border border-border focus:z-20 ${
                                                        link.active
                                                            ? 'z-10 bg-primary text-primary-foreground focus-visible:outline-2'
                                                            : 'text-foreground hover:bg-accent bg-background'
                                                    } ${!link.url ? 'pointer-events-none opacity-50' : ''} ${
                                                        idx === 0 ? 'rounded-l-md' : idx === invitations.links.length - 1 ? 'rounded-r-md' : ''
                                                    }`}
                                                />
                                            ))}
                                        </nav>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

InvitationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Invitations',
            href: '/admin/invitations',
        },
    ],
};
