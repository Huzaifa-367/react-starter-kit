import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, RefreshCw, Loader2, CheckCircle, XCircle } from 'lucide-react';
import { toast } from 'sonner';

interface WebhookLogItem {
    id: number;
    source: 'stripe';
    event_id: string;
    event_type: string;
    payload: any;
    processed: boolean;
    error: string | null;
    created_at: string;
}

interface Props {
    log: WebhookLogItem;
}

export default function WebhookLogsShow({ log }: Props) {
    const [actionLoading, setActionLoading] = useState(false);

    const handleReprocess = () => {
        setActionLoading(true);
        router.post(`/admin/webhook-logs/${log.id}/reprocess`, {}, {
            onSuccess: () => toast.success('Webhook log reprocessed successfully.'),
            onFinish: () => setActionLoading(false),
        });
    };

    const getPayloadString = (payload: any) => {
        if (!payload) return 'No payload data';
        if (typeof payload === 'string') {
            try {
                return JSON.stringify(JSON.parse(payload), null, 2);
            } catch {
                return payload;
            }
        }
        return JSON.stringify(payload, null, 2);
    };

    return (
        <>
            <Head title={`Webhook: ${log.event_id}`} />

            <div className="flex-1 space-y-6 p-6">
                {/* Header */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div className="flex items-center gap-3">
                        <Link href="/admin/webhook-logs">
                            <Button variant="ghost" size="icon" className="h-8 w-8 cursor-pointer">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2 select-all">
                                Webhook: {log.event_id}
                            </h1>
                            <p className="text-muted-foreground text-xs font-mono mt-0.5 uppercase">
                                Source: {log.source} · Type: {log.event_type}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            onClick={handleReprocess}
                            disabled={actionLoading}
                            className="h-9 border-primary/20 hover:bg-primary/5 cursor-pointer"
                        >
                            {actionLoading ? (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin text-primary" />
                            ) : (
                                <RefreshCw className="mr-2 h-4 w-4 text-primary" />
                            )}
                            Reprocess Webhook
                        </Button>
                    </div>
                </div>

                {/* Info Card */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="md:col-span-1 space-y-6">
                        <Card className="border border-border">
                            <CardHeader>
                                <CardTitle className="text-sm font-bold">Metadata</CardTitle>
                                <CardDescription>Webhook delivery metrics.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4 text-xs">
                                <div className="flex justify-between items-center">
                                    <span className="text-muted-foreground">Status:</span>
                                    {log.processed ? (
                                        <Badge className="bg-emerald-500/10 text-emerald-500 border-none font-bold">PROCESSED</Badge>
                                    ) : (
                                        <Badge className="bg-rose-500/10 text-rose-500 border-none font-bold">FAILED</Badge>
                                    )}
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Recorded At:</span>
                                    <span className="font-semibold text-foreground font-mono">
                                        {new Date(log.created_at).toLocaleString()}
                                    </span>
                                </div>
                                <div className="flex flex-col gap-1">
                                    <span className="text-muted-foreground">Event Type:</span>
                                    <span className="font-semibold text-foreground font-mono select-all break-all">
                                        {log.event_type}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        {log.error && (
                            <Card className="border border-rose-500/10 bg-rose-500/5">
                                <CardHeader>
                                    <CardTitle className="text-sm font-bold text-rose-500">Processing Error</CardTitle>
                                    <CardDescription className="text-rose-500/80">Diagnostic stacktrace.</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <pre className="text-rose-600 dark:text-rose-400 font-mono text-[10px] whitespace-pre-wrap leading-relaxed break-all">
                                        {log.error}
                                    </pre>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Payload JSON Card */}
                    <Card className="md:col-span-2 border border-border bg-slate-950 dark:bg-black/80 text-foreground overflow-hidden">
                        <CardHeader className="bg-slate-900/50 border-b border-border/10 py-3">
                            <CardTitle className="text-sm font-bold text-slate-200">Webhook Payload JSON</CardTitle>
                        </CardHeader>
                        <CardContent className="p-4 overflow-x-auto max-h-[70vh] overflow-y-auto">
                            <pre className="text-[10px] font-mono text-slate-300 leading-normal whitespace-pre-wrap">
                                {getPayloadString(log.payload)}
                            </pre>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

WebhookLogsShow.layout = {
    breadcrumbs: [
        {
            title: 'Webhook Logs',
            href: '/admin/webhook-logs',
        },
    ],
};
