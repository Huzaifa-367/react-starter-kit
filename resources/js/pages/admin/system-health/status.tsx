import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { HeartPulse, Database, HardDrive, RefreshCw, Trash2, Cpu, Loader2, AlertTriangle, ShieldCheck } from 'lucide-react';
import { toast } from 'sonner';

interface HealthDetails {
    status: 'ok' | 'failed' | 'warning';
    error?: string | null;
    size?: number;
    free_space?: string;
    total_space?: string;
    usage_percent?: number;
}

interface SystemInfo {
    php_version: string;
    laravel_version: string;
    server_time: string;
}

interface HealthData {
    database: HealthDetails;
    redis: HealthDetails;
    cache: HealthDetails;
    queue: HealthDetails;
    disk: HealthDetails;
    system: SystemInfo;
}

interface Props {
    health: HealthData;
}

export default function SystemHealthStatus({ health }: Props) {
    const [actionLoading, setActionLoading] = useState<string | null>(null);

    const handleRefresh = () => {
        setActionLoading('refresh');
        router.get('/admin/system-health', {}, {
            onSuccess: () => toast.success('Diagnostics status updated.'),
            onFinish: () => setActionLoading(null),
        });
    };

    const handleFlushCache = (category: string, label: string) => {
        if (!confirm(`Are you sure you want to flush the cache for: ${label}?`)) return;
        setActionLoading(category);
        router.post('/admin/cache/flush', { category }, {
            onSuccess: () => toast.success(`${label} cache flushed successfully.`),
            onFinish: () => setActionLoading(null),
        });
    };

    const getStatusIcon = (status: string) => {
        if (status === 'ok') return <ShieldCheck className="h-5 w-5 text-emerald-500" />;
        if (status === 'warning') return <AlertTriangle className="h-5 w-5 text-amber-500" />;
        return <AlertTriangle className="h-5 w-5 text-rose-500" />;
    };

    const getStatusBadge = (status: string) => {
        if (status === 'ok') return <Badge className="bg-emerald-500/10 text-emerald-500 border-none font-bold">ONLINE</Badge>;
        if (status === 'warning') return <Badge className="bg-amber-500/10 text-amber-500 border-none font-bold">WARNING</Badge>;
        return <Badge className="bg-rose-500/10 text-rose-500 border-none font-bold">OFFLINE</Badge>;
    };

    return (
        <>
            <Head title="System Diagnostics" />

            <div className="flex-1 space-y-6 p-6">
                {/* Header */}
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <HeartPulse className="h-6 w-6 text-primary" />
                            System Health Diagnostics
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Real-time platform connection health audits and system cache flush triggers.
                        </p>
                    </div>
                    <div>
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
                            Refresh Audit
                        </Button>
                    </div>
                </div>

                {/* Audit Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {/* Database */}
                    <Card className="border border-border">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <div className="flex items-center gap-2">
                                <Database className="h-5 w-5 text-primary" />
                                <CardTitle className="text-sm font-bold">SQL Database</CardTitle>
                            </div>
                            {getStatusIcon(health.database.status)}
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <div className="flex justify-between items-center text-xs">
                                <span className="text-muted-foreground">Connection Status:</span>
                                {getStatusBadge(health.database.status)}
                            </div>
                            {health.database.error && (
                                <pre className="p-2 bg-rose-500/5 border border-rose-500/10 rounded text-[10px] text-rose-500 font-mono overflow-x-auto max-h-[100px]">
                                    {health.database.error}
                                </pre>
                            )}
                        </CardContent>
                    </Card>

                    {/* Redis */}
                    <Card className="border border-border">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <div className="flex items-center gap-2">
                                <Cpu className="h-5 w-5 text-primary" />
                                <CardTitle className="text-sm font-bold">Redis Cache</CardTitle>
                            </div>
                            {getStatusIcon(health.redis.status)}
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <div className="flex justify-between items-center text-xs">
                                <span className="text-muted-foreground">Server Connection:</span>
                                {getStatusBadge(health.redis.status)}
                            </div>
                            {health.redis.error && (
                                <pre className="p-2 bg-rose-500/5 border border-rose-500/10 rounded text-[10px] text-rose-500 font-mono overflow-x-auto max-h-[100px]">
                                    {health.redis.error}
                                </pre>
                            )}
                        </CardContent>
                    </Card>

                    {/* Queue */}
                    <Card className="border border-border">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <div className="flex items-center gap-2">
                                <RefreshCw className="h-5 w-5 text-primary" />
                                <CardTitle className="text-sm font-bold">Queues (Horizon)</CardTitle>
                            </div>
                            {getStatusIcon(health.queue.status)}
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <div className="flex justify-between items-center text-xs">
                                <span className="text-muted-foreground">Pending Size:</span>
                                <span className="font-bold text-foreground font-mono">{health.queue.size} jobs</span>
                            </div>
                            <div className="flex justify-between items-center text-xs">
                                <span className="text-muted-foreground">Queue Connection:</span>
                                {getStatusBadge(health.queue.status)}
                            </div>
                            {health.queue.error && (
                                <pre className="p-2 bg-rose-500/5 border border-rose-500/10 rounded text-[10px] text-rose-500 font-mono overflow-x-auto max-h-[100px]">
                                    {health.queue.error}
                                </pre>
                            )}
                        </CardContent>
                    </Card>

                    {/* Cache */}
                    <Card className="border border-border">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <div className="flex items-center gap-2">
                                <Cpu className="h-5 w-5 text-primary" />
                                <CardTitle className="text-sm font-bold">Application Cache</CardTitle>
                            </div>
                            {getStatusIcon(health.cache.status)}
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <div className="flex justify-between items-center text-xs">
                                <span className="text-muted-foreground">Read/Write Test:</span>
                                {getStatusBadge(health.cache.status)}
                            </div>
                            {health.cache.error && (
                                <pre className="p-2 bg-rose-500/5 border border-rose-500/10 rounded text-[10px] text-rose-500 font-mono overflow-x-auto max-h-[100px]">
                                    {health.cache.error}
                                </pre>
                            )}
                        </CardContent>
                    </Card>

                    {/* Disk */}
                    <Card className="border border-border">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <div className="flex items-center gap-2">
                                <HardDrive className="h-5 w-5 text-primary" />
                                <CardTitle className="text-sm font-bold">Disk Volume</CardTitle>
                            </div>
                            {getStatusIcon(health.disk.status)}
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="space-y-1">
                                <div className="flex justify-between text-xs">
                                    <span className="text-muted-foreground">Disk usage percentage:</span>
                                    <span className="font-semibold text-foreground font-mono">{health.disk.usage_percent}%</span>
                                </div>
                                <div className="w-full bg-muted rounded-full h-1.5">
                                    <div
                                        className={`h-1.5 rounded-full ${
                                            health.disk.status === 'warning' ? 'bg-amber-500' :
                                            health.disk.status === 'failed' ? 'bg-rose-500' :
                                            'bg-emerald-500'
                                        }`}
                                        style={{ width: `${health.disk.usage_percent}%` }}
                                    />
                                </div>
                            </div>
                            <div className="flex justify-between text-xs">
                                <span className="text-muted-foreground">Free / Total Space:</span>
                                <span className="text-foreground font-mono">{health.disk.free_space} / {health.disk.total_space}</span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Environment Info */}
                    <Card className="border border-border">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <div className="flex items-center gap-2">
                                <Cpu className="h-5 w-5 text-primary" />
                                <CardTitle className="text-sm font-bold">System Specs</CardTitle>
                            </div>
                            <ShieldCheck className="h-5 w-5 text-primary" />
                        </CardHeader>
                        <CardContent className="space-y-2 text-xs">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">PHP Version:</span>
                                <span className="font-semibold text-foreground font-mono">{health.system.php_version}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Laravel Framework:</span>
                                <span className="font-semibold text-foreground font-mono">v{health.system.laravel_version}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Host Server Time:</span>
                                <span className="font-semibold text-foreground font-mono text-[10px]">
                                    {new Date(health.system.server_time).toLocaleString()}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Cache Management Console */}
                <Card className="border border-border">
                    <CardHeader>
                        <CardTitle className="text-lg font-bold">Flush Cache Management</CardTitle>
                        <CardDescription>
                            Clear cache tables. Flushes are synchronized across all Redis instances.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                        {[
                            { category: 'settings', label: 'App Settings', desc: 'SaaS configs & SMTP keys.' },
                            { category: 'feature_flags', label: 'Feature Flags', desc: 'Enable/disable toggle criteria.' },
                            { category: 'ip_rules', label: 'IP Rules', desc: 'Firewall blocklists and allowlists.' },
                            { category: 'subscriptions', label: 'Subscriptions', desc: 'Plan titles & entitlements lists.' },
                            { category: 'all', label: 'Flush Everything', desc: 'Wipes Redis memory clean.', danger: true },
                        ].map((c) => (
                            <div key={c.category} className="p-4 bg-muted/30 border border-border rounded-lg flex flex-col justify-between gap-3 text-center">
                                <div>
                                    <h3 className="font-semibold text-sm">{c.label}</h3>
                                    <p className="text-[10px] text-muted-foreground mt-1">{c.desc}</p>
                                </div>
                                <Button
                                    variant={c.danger ? 'destructive' : 'outline'}
                                    size="sm"
                                    onClick={() => handleFlushCache(c.category, c.label)}
                                    disabled={actionLoading === c.category}
                                    className="w-full cursor-pointer"
                                >
                                    {actionLoading === c.category ? (
                                        <Loader2 className="h-3.5 w-3.5 animate-spin mr-1" />
                                    ) : (
                                        <Trash2 className="h-3.5 w-3.5 mr-1" />
                                    )}
                                    Clear
                                </Button>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

SystemHealthStatus.layout = {
    breadcrumbs: [
        {
            title: 'System Health',
            href: '/admin/system-health',
        },
    ],
};
