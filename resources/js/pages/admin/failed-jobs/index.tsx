import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { RefreshCw, Trash2, ShieldAlert, Eye, Copy, Loader2 } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { toast } from 'sonner';

interface FailedJobItem {
    id: number;
    uuid: string;
    connection: string;
    queue: string;
    payload: string;
    exception: string;
    failed_at: string;
}

interface Props {
    failedJobs: {
        data: FailedJobItem[];
        links: any[];
        total: number;
        current_page: number;
        last_page: number;
    };
}

export default function FailedJobsIndex({ failedJobs }: Props) {
    const [actionLoading, setActionLoading] = useState<string | null>(null);
    const [selectedJob, setSelectedJob] = useState<FailedJobItem | null>(null);
    const [detailOpen, setDetailOpen] = useState(false);

    const handleRetry = (uuid: string) => {
        setActionLoading(`retry-${uuid}`);
        router.post(`/admin/failed-jobs/${uuid}/retry`, {}, {
            onSuccess: () => toast.success('Job has been queued for retry.'),
            onFinish: () => setActionLoading(null),
        });
    };

    const handleDelete = (uuid: string) => {
        if (!confirm('Are you sure you want to delete this failed job from the queue?')) return;
        setActionLoading(`delete-${uuid}`);
        router.delete(`/admin/failed-jobs/${uuid}`, {
            onSuccess: () => toast.success('Failed job deleted.'),
            onFinish: () => setActionLoading(null),
        });
    };

    const handleRetryAll = () => {
        if (!confirm('Are you sure you want to retry ALL failed jobs? This will queue them in Redis.')) return;
        setActionLoading('retry-all');
        router.post('/admin/failed-jobs/retry-all', {}, {
            onSuccess: () => toast.success('All failed jobs have been queued for retry.'),
            onFinish: () => setActionLoading(null),
        });
    };

    const handleFlushAll = () => {
        if (!confirm('WARNING: Are you sure you want to permanently delete ALL failed jobs? This cannot be undone.')) return;
        setActionLoading('flush-all');
        router.delete('/admin/failed-jobs', {
            onSuccess: () => toast.success('All failed jobs deleted.'),
            onFinish: () => setActionLoading(null),
        });
    };

    const handleCopyException = (text: string) => {
        navigator.clipboard.writeText(text);
        toast.success('Exception trace copied to clipboard.');
    };

    const getJobName = (payloadStr: string) => {
        try {
            const payload = JSON.parse(payloadStr);
            return payload.displayName || payload.job || 'Unknown Job';
        } catch {
            return 'Parse Error';
        }
    };

    const getErrorMessage = (exceptionStr: string) => {
        if (!exceptionStr) return 'No exception trace';
        const firstLine = exceptionStr.split("\n")[0];
        return firstLine.length > 120 ? `${firstLine.substring(0, 120)}...` : firstLine;
    };

    return (
        <>
            <Head title="Failed Jobs Monitor" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            Failed Queue Jobs
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Monitor queue failures, inspect exception traces, and trigger job retries.
                        </p>
                    </div>
                    {failedJobs.data.length > 0 && (
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={handleRetryAll}
                                disabled={actionLoading === 'retry-all'}
                                className="border-primary/30 text-primary hover:bg-primary/5 cursor-pointer"
                            >
                                {actionLoading === 'retry-all' ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : (
                                    <RefreshCw className="mr-2 h-4 w-4" />
                                )}
                                Retry All
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={handleFlushAll}
                                disabled={actionLoading === 'flush-all'}
                                className="border-rose-200 text-rose-600 dark:border-rose-900/50 hover:bg-rose-50 dark:hover:bg-rose-950/20 cursor-pointer"
                            >
                                {actionLoading === 'flush-all' ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : (
                                    <Trash2 className="mr-2 h-4 w-4" />
                                )}
                                Flush All
                            </Button>
                        </div>
                    )}
                </div>

                {/* Table */}
                <Card className="border border-border">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="bg-muted/40 text-xs font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                                        <th className="p-4">Job</th>
                                        <th className="p-4">Queue / Connection</th>
                                        <th className="p-4">Error Exception</th>
                                        <th className="p-4">Failed At</th>
                                        <th className="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border text-sm">
                                    {failedJobs.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="p-8 text-center text-muted-foreground font-medium">
                                                <div className="flex flex-col items-center justify-center gap-2 py-4">
                                                    <Badge className="bg-emerald-500/10 text-emerald-500 border-none font-bold">ALL CLEAR</Badge>
                                                    <span>No failed queue jobs found. Excellent!</span>
                                                </div>
                                            </td>
                                        </tr>
                                    ) : (
                                        failedJobs.data.map((job) => (
                                            <tr key={job.id} className="hover:bg-muted/10 transition-colors">
                                                <td className="p-4 max-w-xs">
                                                    <div className="flex flex-col">
                                                        <span className="font-semibold text-foreground font-mono text-xs truncate">
                                                            {getJobName(job.payload)}
                                                        </span>
                                                        <span className="text-[10px] text-muted-foreground font-mono mt-0.5 select-all">
                                                            UUID: {job.uuid}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="p-4">
                                                    <div className="flex flex-col">
                                                        <span className="font-semibold">Queue: {job.queue}</span>
                                                        <span className="text-xs text-muted-foreground font-mono">Conn: {job.connection}</span>
                                                    </div>
                                                </td>
                                                <td className="p-4 text-rose-600 dark:text-rose-400 font-mono text-xs max-w-sm truncate">
                                                    {getErrorMessage(job.exception)}
                                                </td>
                                                <td className="p-4 text-muted-foreground">
                                                    {new Date(job.failed_at).toLocaleString()}
                                                </td>
                                                <td className="p-4 text-right space-x-1.5 whitespace-nowrap">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => {
                                                            setSelectedJob(job);
                                                            setDetailOpen(true);
                                                        }}
                                                        className="h-8 text-xs cursor-pointer"
                                                    >
                                                        <Eye className="h-3.5 w-3.5 mr-1" />
                                                        Details
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleRetry(job.uuid)}
                                                        disabled={actionLoading === `retry-${job.uuid}`}
                                                        className="h-8 text-xs cursor-pointer"
                                                    >
                                                        {actionLoading === `retry-${job.uuid}` ? (
                                                            <Loader2 className="h-3 w-3 animate-spin mr-1" />
                                                        ) : (
                                                            <RefreshCw className="h-3 w-3 mr-1 text-primary" />
                                                        )}
                                                        Retry
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => handleDelete(job.uuid)}
                                                        disabled={actionLoading === `delete-${job.uuid}`}
                                                        className="h-8 w-8 hover:bg-rose-500/10 hover:text-rose-500 cursor-pointer"
                                                    >
                                                        {actionLoading === `delete-${job.uuid}` ? (
                                                            <Loader2 className="h-3 w-3 animate-spin" />
                                                        ) : (
                                                            <Trash2 className="h-4 w-4" />
                                                        )}
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {failedJobs.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-border px-4 py-3 sm:px-6">
                                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                    <p className="text-sm text-muted-foreground">
                                        Showing page <span className="font-semibold text-foreground">{failedJobs.current_page}</span> of{' '}
                                        <span className="font-semibold text-foreground">{failedJobs.last_page}</span> ({failedJobs.total} total failures)
                                    </p>
                                    <nav className="isolate inline-flex -space-x-px rounded-md shadow-xs" aria-label="Pagination">
                                        {failedJobs.links.map((link: any, idx: number) => (
                                            <Link
                                                key={idx}
                                                href={link.url || '#'}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                className={`relative inline-flex items-center px-4 py-2 text-sm font-semibold border border-border focus:z-20 ${
                                                    link.active
                                                        ? 'z-10 bg-primary text-primary-foreground focus-visible:outline-2'
                                                        : 'text-foreground hover:bg-accent bg-background'
                                                } ${!link.url ? 'pointer-events-none opacity-50' : ''} ${
                                                    idx === 0 ? 'rounded-l-md' : idx === failedJobs.links.length - 1 ? 'rounded-r-md' : ''
                                                }`}
                                            />
                                        ))}
                                    </nav>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Details Dialog */}
                <Dialog open={detailOpen} onOpenChange={setDetailOpen}>
                    <DialogContent className="max-w-3xl max-h-[85vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-rose-500">
                                <ShieldAlert className="h-5 w-5" />
                                Failed Job details
                            </DialogTitle>
                            <DialogDescription className="font-mono text-xs">
                                UUID: {selectedJob?.uuid}<br />
                                Failed At: {selectedJob && new Date(selectedJob.failed_at).toLocaleString()}
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4 py-2">
                            <div className="grid grid-cols-2 gap-4 text-xs">
                                <div className="p-2.5 bg-muted rounded-md border border-border">
                                    <strong>Connection:</strong> {selectedJob?.connection}<br />
                                    <strong>Queue:</strong> {selectedJob?.queue}
                                </div>
                                <div className="p-2.5 bg-muted rounded-md border border-border">
                                    <strong>Job class:</strong> {selectedJob && getJobName(selectedJob.payload)}
                                </div>
                            </div>

                            <div>
                                <span className="text-foreground font-semibold block mb-1 text-sm">Payload JSON</span>
                                <pre className="p-3 bg-muted text-foreground text-[10px] font-mono rounded-md border border-border overflow-x-auto max-h-[150px]">
                                    {selectedJob && JSON.stringify(JSON.parse(selectedJob.payload), null, 2)}
                                </pre>
                            </div>

                            <div>
                                <div className="flex justify-between items-center mb-1">
                                    <span className="text-rose-500 font-semibold text-sm">Exception Trace</span>
                                    <Button
                                        variant="ghost"
                                        size="xs"
                                        onClick={() => handleCopyException(selectedJob?.exception || '')}
                                        className="h-7 text-[10px]"
                                    >
                                        <Copy className="h-3 w-3 mr-1" />
                                        Copy Trace
                                    </Button>
                                </div>
                                <pre className="p-3 bg-rose-50 dark:bg-rose-950/20 text-rose-900 dark:text-rose-200 text-[10px] font-mono rounded-md border border-rose-200/50 dark:border-rose-900/50 overflow-x-auto max-h-[250px] whitespace-pre-wrap">
                                    {selectedJob?.exception}
                                </pre>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button variant="outline" onClick={() => setDetailOpen(false)}>Close</Button>
                            {selectedJob && (
                                <Button
                                    onClick={() => {
                                        handleRetry(selectedJob.uuid);
                                        setDetailOpen(false);
                                    }}
                                    disabled={actionLoading === `retry-${selectedJob.uuid}`}
                                >
                                    Retry Job
                                </Button>
                            )}
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

FailedJobsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Failed Jobs',
            href: '/admin/failed-jobs',
        },
    ],
};
