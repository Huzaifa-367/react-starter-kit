import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Radio, Send, Trash2, Plus, Loader2, RefreshCw, AlertCircle } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { toast } from 'sonner';

interface BroadcastItem {
    id: number;
    admin_id: number | null;
    title: string;
    body: string;
    channels: string[];
    target_type: 'all' | 'plan' | 'role' | 'segment';
    target_id: number | null;
    status: 'draft' | 'scheduled' | 'sending' | 'sent' | 'failed';
    scheduled_at: string | null;
    sent_at: string | null;
    total_recipients: number;
    sent_count: number;
    failed_count: number;
    created_at: string;
    admin?: {
        name: string;
        email: string;
    } | null;
}

interface OptionItem {
    id: number;
    name: string;
}

interface Props {
    broadcasts: {
        data: BroadcastItem[];
        links: any[];
        total: number;
        current_page: number;
        last_page: number;
    };
    plans: OptionItem[];
    roles: OptionItem[];
    segments: OptionItem[];
}

export default function BroadcastIndex({ broadcasts, plans, roles, segments }: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [actionLoading, setActionLoading] = useState<string | null>(null);

    // Form states
    const [title, setTitle] = useState('');
    const [body, setBody] = useState('');
    const [channels, setChannels] = useState<string[]>(['email']);
    const [targetType, setTargetType] = useState<'all' | 'plan' | 'role' | 'segment'>('all');
    const [targetId, setTargetId] = useState<string>('');
    const [scheduledAt, setScheduledAt] = useState('');
    const [previewCount, setPreviewCount] = useState<number | null>(null);
    const [previewLoading, setPreviewLoading] = useState(false);

    // Fetch estimated audience size
    useEffect(() => {
        if (!createOpen) return;
        
        const fetchPreviewCount = async () => {
            setPreviewLoading(true);
            try {
                const response = await fetch('/admin/broadcasts/preview', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                    },
                    body: JSON.stringify({
                        target_type: targetType,
                        target_id: targetId ? parseInt(targetId) : null,
                    }),
                });
                const data = await response.json();
                setPreviewCount(data.count);
            } catch (e) {
                setPreviewCount(0);
            } finally {
                setPreviewLoading(false);
            }
        };

        const timer = setTimeout(() => {
            fetchPreviewCount();
        }, 300);

        return () => clearTimeout(timer);
    }, [targetType, targetId, createOpen]);

    const handleChannelToggle = (ch: string) => {
        if (channels.includes(ch)) {
            if (channels.length > 1) {
                setChannels(channels.filter(c => c !== ch));
            }
        } else {
            setChannels([...channels, ch]);
        }
    };

    const handleCreateBroadcast = (e: React.FormEvent) => {
        e.preventDefault();
        setActionLoading('create');
        router.post('/admin/broadcasts', {
            title,
            body,
            channels,
            target_type: targetType,
            target_id: targetId ? parseInt(targetId) : null,
            scheduled_at: scheduledAt || null,
        }, {
            onSuccess: () => {
                toast.success('Broadcast campaign saved successfully.');
                setCreateOpen(false);
                setTitle('');
                setBody('');
                setChannels(['email']);
                setTargetType('all');
                setTargetId('');
                setScheduledAt('');
            },
            onError: (err) => {
                toast.error(Object.values(err)[0] || 'Verification failed.');
            },
            onFinish: () => setActionLoading(null),
        });
    };

    const handleSendNow = (b: BroadcastItem) => {
        if (!confirm(`Are you sure you want to dispatch broadcast campaign "${b.title}" to ${b.total_recipients} users immediately?`)) return;
        setActionLoading(`send-${b.id}`);
        router.post(`/admin/broadcasts/${b.id}/send`, {}, {
            onSuccess: () => toast.success('Broadcast campaign queued for delivery.'),
            onFinish: () => setActionLoading(null),
        });
    };

    const handleDelete = (b: BroadcastItem) => {
        if (!confirm(`Are you sure you want to delete campaign "${b.title}"?`)) return;
        setActionLoading(`delete-${b.id}`);
        router.delete(`/admin/broadcasts/${b.id}`, {
            onSuccess: () => toast.success('Broadcast campaign deleted successfully.'),
            onFinish: () => setActionLoading(null),
        });
    };

    const getTargetLabel = (type: string, id: number | null) => {
        if (type === 'all') return 'All Users';
        if (type === 'plan') {
            const plan = plans.find(p => p.id === id);
            return `Plan: ${plan ? plan.name : 'Unknown'}`;
        }
        if (type === 'role') {
            const role = roles.find(r => r.id === id);
            return `Role: ${role ? role.name : 'Unknown'}`;
        }
        if (type === 'segment') {
            const seg = segments.find(s => s.id === id);
            return `Segment: ${seg ? seg.name : 'Unknown'}`;
        }
        return 'Unknown';
    };

    return (
        <>
            <Head title="Broadcast Notifications" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">Broadcast Notifications</h1>
                        <p className="text-muted-foreground text-sm">
                            Configure bulk multi-channel push, email, SMS, and WhatsApp alerts for customer cohorts.
                        </p>
                    </div>
                    <div>
                        <Button onClick={() => setCreateOpen(true)} className="cursor-pointer">
                            <Plus className="mr-2 h-4 w-4" />
                            New Broadcast
                        </Button>
                    </div>
                </div>

                {/* Table list */}
                <Card className="border border-border">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="bg-muted/40 text-xs font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                                        <th className="p-4">Title & Body</th>
                                        <th className="p-4">Channels</th>
                                        <th className="p-4">Audience</th>
                                        <th className="p-4">Status</th>
                                        <th className="p-4">Stats</th>
                                        <th className="p-4">Timing</th>
                                        <th className="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border text-sm">
                                    {broadcasts.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="p-8 text-center text-muted-foreground">
                                                No broadcast alerts configured.
                                            </td>
                                        </tr>
                                    ) : (
                                        broadcasts.data.map((b) => (
                                            <tr key={b.id} className="hover:bg-muted/10 transition-colors">
                                                <td className="p-4 max-w-sm">
                                                    <div className="flex flex-col">
                                                        <span className="font-semibold text-foreground">{b.title}</span>
                                                        <span className="text-xs text-muted-foreground line-clamp-2 mt-0.5">{b.body}</span>
                                                    </div>
                                                </td>
                                                <td className="p-4">
                                                    <div className="flex flex-wrap gap-1">
                                                        {b.channels.map((ch) => (
                                                            <Badge key={ch} variant="outline" className="text-[10px] uppercase">
                                                                {ch}
                                                            </Badge>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="p-4">
                                                    <Badge variant="secondary" className="text-[10px]">
                                                        {getTargetLabel(b.target_type, b.target_id)}
                                                    </Badge>
                                                </td>
                                                <td className="p-4">
                                                    <span className={`px-2 py-0.5 rounded text-xs font-semibold capitalize ${
                                                        b.status === 'sent' ? 'bg-emerald-500/10 text-emerald-500' :
                                                        b.status === 'sending' ? 'bg-blue-500/10 text-blue-500' :
                                                        b.status === 'scheduled' ? 'bg-amber-500/10 text-amber-500' :
                                                        b.status === 'failed' ? 'bg-rose-500/10 text-rose-500' :
                                                        'bg-gray-500/10 text-gray-500'
                                                    }`}>
                                                        {b.status}
                                                    </span>
                                                </td>
                                                <td className="p-4">
                                                    <div className="flex flex-col text-xs">
                                                        <span className="font-semibold">Target: {b.total_recipients}</span>
                                                        {(b.status === 'sent' || b.status === 'sending' || b.status === 'failed') && (
                                                            <span className="text-muted-foreground">
                                                                Sent: <span className="text-emerald-500 font-bold">{b.sent_count}</span> · Fail: <span className="text-rose-500 font-bold">{b.failed_count}</span>
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="p-4 text-xs text-muted-foreground">
                                                    {b.scheduled_at ? (
                                                        <div className="flex flex-col">
                                                            <span>Sched: {new Date(b.scheduled_at).toLocaleString()}</span>
                                                            {b.sent_at && <span>Done: {new Date(b.sent_at).toLocaleString()}</span>}
                                                        </div>
                                                    ) : (
                                                        <span>Immediate {b.sent_at ? `(${new Date(b.sent_at).toLocaleString()})` : ''}</span>
                                                    )}
                                                </td>
                                                <td className="p-4 text-right space-x-1.5 whitespace-nowrap">
                                                    {(b.status === 'draft' || b.status === 'scheduled') && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleSendNow(b)}
                                                            disabled={actionLoading === `send-${b.id}`}
                                                            className="h-8 text-xs cursor-pointer"
                                                        >
                                                            {actionLoading === `send-${b.id}` ? (
                                                                <Loader2 className="h-3 w-3 animate-spin mr-1" />
                                                            ) : (
                                                                <Send className="h-3 w-3 mr-1" />
                                                            )}
                                                            Dispatch
                                                        </Button>
                                                    )}
                                                    {(b.status === 'draft' || b.status === 'scheduled') && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => handleDelete(b)}
                                                            disabled={actionLoading === `delete-${b.id}`}
                                                            className="h-8 w-8 hover:bg-rose-500/10 hover:text-rose-500 cursor-pointer"
                                                        >
                                                            {actionLoading === `delete-${b.id}` ? (
                                                                <Loader2 className="h-3 w-3 animate-spin" />
                                                            ) : (
                                                                <Trash2 className="h-4 w-4" />
                                                            )}
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {broadcasts.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-border px-4 py-3 sm:px-6">
                                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                    <p className="text-sm text-muted-foreground">
                                        Showing page <span className="font-semibold text-foreground">{broadcasts.current_page}</span> of{' '}
                                        <span className="font-semibold text-foreground">{broadcasts.last_page}</span> ({broadcasts.total} total campaigns)
                                    </p>
                                    <nav className="isolate inline-flex -space-x-px rounded-md shadow-xs" aria-label="Pagination">
                                        {broadcasts.links.map((link: any, idx: number) => (
                                            <Link
                                                key={idx}
                                                href={link.url || '#'}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                className={`relative inline-flex items-center px-4 py-2 text-sm font-semibold border border-border focus:z-20 ${
                                                    link.active
                                                        ? 'z-10 bg-primary text-primary-foreground focus-visible:outline-2'
                                                        : 'text-foreground hover:bg-accent bg-background'
                                                } ${!link.url ? 'pointer-events-none opacity-50' : ''} ${
                                                    idx === 0 ? 'rounded-l-md' : idx === broadcasts.links.length - 1 ? 'rounded-r-md' : ''
                                                }`}
                                            />
                                        ))}
                                    </nav>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Campaign Builder Dialog */}
                <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                    <DialogContent className="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>New Broadcast Campaign</DialogTitle>
                            <DialogDescription>
                                Dispatch system-wide alerts. Estimates are evaluated based on selected targets.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleCreateBroadcast} className="space-y-4 py-2">
                            <div className="grid gap-2">
                                <Label htmlFor="title">Notification Title</Label>
                                <Input
                                    id="title"
                                    placeholder="Enter subject or title..."
                                    value={title}
                                    onChange={(e) => setTitle(e.target.value)}
                                    required
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="body">Message Body</Label>
                                <textarea
                                    id="body"
                                    rows={4}
                                    placeholder="Type message content here..."
                                    value={body}
                                    onChange={(e) => setBody(e.target.value)}
                                    className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    required
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label>Select Channels</Label>
                                <div className="flex flex-wrap gap-4 mt-1">
                                    {['email', 'fcm', 'whatsapp', 'sms'].map((ch) => (
                                        <label key={ch} className="flex items-center gap-2 text-sm cursor-pointer select-none">
                                            <input
                                                type="checkbox"
                                                checked={channels.includes(ch)}
                                                onChange={() => handleChannelToggle(ch)}
                                                className="rounded border-input text-primary focus:ring-primary h-4 w-4"
                                            />
                                            <span className="uppercase font-mono text-xs">{ch === 'fcm' ? 'Push (FCM)' : ch}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="target_type">Target Audience Type</Label>
                                    <select
                                        id="target_type"
                                        value={targetType}
                                        onChange={(e) => {
                                            setTargetType(e.target.value as any);
                                            setTargetId('');
                                        }}
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    >
                                        <option value="all">All Users</option>
                                        <option value="plan">By Subscription Plan</option>
                                        <option value="role">By User Role</option>
                                        <option value="segment">By Saved Cohort Segment</option>
                                    </select>
                                </div>

                                {targetType !== 'all' && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="target_id">Select cohort</Label>
                                        <select
                                            id="target_id"
                                            value={targetId}
                                            onChange={(e) => setTargetId(e.target.value)}
                                            required
                                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                        >
                                            <option value="">-- Select Target --</option>
                                            {targetType === 'plan' && plans.map(p => (
                                                <option key={p.id} value={p.id}>{p.name}</option>
                                            ))}
                                            {targetType === 'role' && roles.map(r => (
                                                <option key={r.id} value={r.id}>{r.name}</option>
                                            ))}
                                            {targetType === 'segment' && segments.map(s => (
                                                <option key={s.id} value={s.id}>{s.name}</option>
                                            ))}
                                        </select>
                                    </div>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="scheduled_at">Schedule Delivery Time (Optional)</Label>
                                <Input
                                    id="scheduled_at"
                                    type="datetime-local"
                                    value={scheduledAt}
                                    onChange={(e) => setScheduledAt(e.target.value)}
                                />
                                <span className="text-[10px] text-muted-foreground">Leave blank to dispatch immediately on submission.</span>
                            </div>

                            {/* Live Target Audience Preview */}
                            <div className="p-3 bg-muted/50 rounded-md border border-border flex items-center justify-between text-xs">
                                <span className="text-muted-foreground">Estimated Audience Size:</span>
                                <div className="flex items-center gap-1.5 font-bold text-foreground">
                                    {previewLoading ? (
                                        <Loader2 className="h-3 w-3 animate-spin text-primary" />
                                    ) : (
                                        <span className="text-primary">{previewCount !== null ? previewCount : 0} users</span>
                                    )}
                                </div>
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setCreateOpen(false)}>Cancel</Button>
                                <Button type="submit" disabled={actionLoading === 'create' || previewLoading} className="cursor-pointer">
                                    {actionLoading === 'create' && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    {scheduledAt ? 'Schedule Alert' : 'Dispatch Alert'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

BroadcastIndex.layout = {
    breadcrumbs: [
        {
            title: 'Broadcasts',
            href: '/admin/broadcasts',
        },
    ],
};
