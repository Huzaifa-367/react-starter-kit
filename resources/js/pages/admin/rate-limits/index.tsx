import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Unlock, ShieldAlert, Loader2, RefreshCw } from 'lucide-react';
import { toast } from 'sonner';

interface LockItem {
    key: string;
    hits: number;
    ttl: number;
    expires_at: string;
}

interface Props {
    locks: LockItem[];
}

export default function RateLimitsIndex({ locks }: Props) {
    const [actionLoading, setActionLoading] = useState<string | null>(null);

    const handleUnlock = (key: string) => {
        setActionLoading(key);
        router.post('/admin/rate-limits/unlock', { key }, {
            onSuccess: () => {
                toast.success('Rate limit lock removed successfully.');
            },
            onFinish: () => setActionLoading(null),
        });
    };

    const handleRefresh = () => {
        setActionLoading('refresh');
        router.get('/admin/rate-limits', {}, {
            onSuccess: () => toast.success('Rate limit locks synced.'),
            onFinish: () => setActionLoading(null),
        });
    };

    const formatTtl = (seconds: number) => {
        if (seconds <= 0) return 'Expired';
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return mins > 0 ? `${mins}m ${secs}s` : `${secs}s`;
    };

    const parseLockKey = (key: string) => {
        // e.g., "illuminate:limiter:127.0.0.1:login"
        const parts = key.split(':');
        return {
            raw: key,
            ip: parts[parts.length - 2] || 'Unknown',
            action: parts[parts.length - 1] || 'request',
        };
    };

    return (
        <>
            <Head title="Locked Rate Limits" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            Locked Rate Limits
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Inspect rate-limiting IP blocks stored in the Redis cache and force-unlock blocked hosts.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleRefresh}
                            disabled={actionLoading === 'refresh'}
                            className="cursor-pointer"
                        >
                            {actionLoading === 'refresh' ? (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <RefreshCw className="mr-2 h-4 w-4" />
                            )}
                            Sync Cache
                        </Button>
                    </div>
                </div>

                {/* Table */}
                <Card className="border border-border">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="bg-muted/40 text-xs font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                                        <th className="p-4">Target IP / Identifier</th>
                                        <th className="p-4">Throttled Action Route</th>
                                        <th className="p-4">Recorded Hits</th>
                                        <th className="p-4">Remaining Lock (TTL)</th>
                                        <th className="p-4">Unlocks At</th>
                                        <th className="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border text-sm font-mono">
                                    {locks.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="p-8 text-center text-muted-foreground font-sans font-medium">
                                                <div className="flex flex-col items-center justify-center gap-2 py-4">
                                                    <Badge className="bg-emerald-500/10 text-emerald-500 border-none font-bold">ALL CLEAR</Badge>
                                                    <span>No active rate limit lockouts found in Redis cache.</span>
                                                </div>
                                            </td>
                                        </tr>
                                    ) : (
                                        locks.map((lock) => {
                                            const info = parseLockKey(lock.key);
                                            return (
                                                <tr key={lock.key} className="hover:bg-muted/10 transition-colors">
                                                    <td className="p-4 font-semibold text-foreground text-sm select-all">
                                                        {info.ip}
                                                    </td>
                                                    <td className="p-4 text-xs font-semibold capitalize font-sans">
                                                        <Badge variant="outline" className="bg-primary/5 text-primary">
                                                            {info.action}
                                                        </Badge>
                                                    </td>
                                                    <td className="p-4 font-semibold text-rose-500 font-sans">
                                                        {lock.hits} attempts
                                                    </td>
                                                    <td className="p-4 text-amber-500 font-semibold font-sans">
                                                        {formatTtl(lock.ttl)}
                                                    </td>
                                                    <td className="p-4 text-muted-foreground font-sans text-xs">
                                                        {new Date(lock.expires_at).toLocaleString()}
                                                    </td>
                                                    <td className="p-4 text-right font-sans">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleUnlock(lock.key)}
                                                            disabled={actionLoading === lock.key}
                                                            className="h-8 text-xs border-emerald-200 text-emerald-600 dark:border-emerald-900/50 hover:bg-emerald-50 dark:hover:bg-emerald-950/20 cursor-pointer"
                                                        >
                                                            {actionLoading === lock.key ? (
                                                                <Loader2 className="h-3 w-3 animate-spin mr-1" />
                                                            ) : (
                                                                <Unlock className="h-3 w-3 mr-1" />
                                                            )}
                                                            Force Unlock
                                                        </Button>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

RateLimitsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Rate Limits',
            href: '/admin/rate-limits',
        },
    ],
};
