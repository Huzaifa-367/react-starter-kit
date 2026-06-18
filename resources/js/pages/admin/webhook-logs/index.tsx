import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Webhook, Eye, RefreshCw, Loader2, CheckCircle, XCircle } from 'lucide-react';
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
    logs: {
        data: WebhookLogItem[];
        links: any[];
        total: number;
        current_page: number;
        last_page: number;
    };
}

export default function WebhookLogsIndex({ logs }: Props) {
    const [actionLoading, setActionLoading] = useState<string | null>(null);

    const handleReprocess = (id: number) => {
        setActionLoading(`reprocess-${id}`);
        router.post(`/admin/webhook-logs/${id}/reprocess`, {}, {
            onSuccess: () => toast.success('Webhook log reprocessed successfully.'),
            onFinish: () => setActionLoading(null),
        });
    };

    return (
        <>
            <Head title="Stripe Webhook Logs" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <Webhook className="h-6 w-6 text-primary" />
                            Stripe Webhook Logs
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Inspect incoming payment notifications. View payload parameters or trigger manual reprocessing.
                        </p>
                    </div>
                </div>

                {/* Table */}
                <Card className="border border-border">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="bg-muted/40 text-xs font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                                        <th className="p-4">Event ID / Source</th>
                                        <th className="p-4">Stripe Event Type</th>
                                        <th className="p-4">Processing Status</th>
                                        <th className="p-4">Processing Error</th>
                                        <th className="p-4">Timestamp</th>
                                        <th className="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border text-sm font-mono">
                                    {logs.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="p-8 text-center text-muted-foreground font-sans">
                                                No webhook events recorded.
                                            </td>
                                        </tr>
                                    ) : (
                                        logs.data.map((log) => (
                                            <tr key={log.id} className="hover:bg-muted/10 transition-colors">
                                                <td className="p-4">
                                                    <div className="flex flex-col">
                                                        <span className="font-semibold text-foreground text-xs select-all">
                                                            {log.event_id}
                                                        </span>
                                                        <span className="text-[10px] font-sans font-bold text-muted-foreground uppercase mt-0.5">
                                                            Source: {log.source}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="p-4">
                                                    <Badge variant="outline" className="text-[10px] font-semibold bg-primary/5 text-primary border-none select-all">
                                                        {log.event_type}
                                                    </Badge>
                                                </td>
                                                <td className="p-4 font-sans">
                                                    {log.processed ? (
                                                        <Badge className="bg-emerald-500/10 text-emerald-500 border-none font-bold flex items-center w-max gap-1">
                                                            <CheckCircle className="h-3 w-3" /> PROCESSED
                                                        </Badge>
                                                    ) : (
                                                        <Badge className="bg-rose-500/10 text-rose-500 border-none font-bold flex items-center w-max gap-1">
                                                            <XCircle className="h-3 w-3" /> FAILED
                                                        </Badge>
                                                    )}
                                                </td>
                                                <td className="p-4 text-rose-500 font-sans text-xs max-w-xs truncate">
                                                    {log.error || '—'}
                                                </td>
                                                <td className="p-4 text-muted-foreground font-sans text-xs">
                                                    {new Date(log.created_at).toLocaleString()}
                                                </td>
                                                <td className="p-4 text-right font-sans space-x-1.5 whitespace-nowrap">
                                                    <Link href={`/admin/webhook-logs/${log.id}`}>
                                                        <Button variant="ghost" size="sm" className="h-8 text-xs cursor-pointer">
                                                            <Eye className="h-3.5 w-3.5 mr-1" />
                                                            Inspect
                                                        </Button>
                                                    </Link>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleReprocess(log.id)}
                                                        disabled={actionLoading === `reprocess-${log.id}`}
                                                        className="h-8 text-xs border-primary/20 hover:bg-primary/5 cursor-pointer"
                                                    >
                                                        {actionLoading === `reprocess-${log.id}` ? (
                                                            <Loader2 className="h-3 w-3 animate-spin mr-1 text-primary" />
                                                        ) : (
                                                            <RefreshCw className="h-3 w-3 mr-1 text-primary" />
                                                        )}
                                                        Reprocess
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {logs.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-border px-4 py-3 sm:px-6">
                                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between font-sans">
                                    <p className="text-sm text-muted-foreground">
                                        Showing page <span className="font-semibold text-foreground">{logs.current_page}</span> of{' '}
                                        <span className="font-semibold text-foreground">{logs.last_page}</span> ({logs.total} total events)
                                    </p>
                                    <nav className="isolate inline-flex -space-x-px rounded-md shadow-xs" aria-label="Pagination">
                                        {logs.links.map((link: any, idx: number) => (
                                            <Link
                                                key={idx}
                                                href={link.url || '#'}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                className={`relative inline-flex items-center px-4 py-2 text-sm font-semibold border border-border focus:z-20 ${
                                                    link.active
                                                        ? 'z-10 bg-primary text-primary-foreground focus-visible:outline-2'
                                                        : 'text-foreground hover:bg-accent bg-background'
                                                } ${!link.url ? 'pointer-events-none opacity-50' : ''} ${
                                                    idx === 0 ? 'rounded-l-md' : idx === logs.links.length - 1 ? 'rounded-r-md' : ''
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
        </>
    );
}

WebhookLogsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Webhook Logs',
            href: '/admin/webhook-logs',
        },
    ],
};
