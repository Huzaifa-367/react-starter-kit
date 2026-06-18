import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Search, Download, RefreshCw, Eye } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';

interface LogItem {
    id: number;
    user_id: number | null;
    subject_type: string | null;
    subject_id: number | null;
    event: string;
    description: string | null;
    old_values: any;
    new_values: any;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
    } | null;
}

interface Props {
    logs: {
        data: LogItem[];
        links: any[];
        total: number;
        current_page: number;
        last_page: number;
    };
    filters: {
        user_id?: string;
        event?: string;
        subject_type?: string;
        start_date?: string;
        end_date?: string;
    };
}

export default function ActivityLogsIndex({ logs, filters }: Props) {
    const [userId, setUserId] = useState(filters.user_id || '');
    const [event, setEvent] = useState(filters.event || '');
    const [subjectType, setSubjectType] = useState(filters.subject_type || '');
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');

    // Detail modal state
    const [detailOpen, setDetailOpen] = useState(false);
    const [selectedLog, setSelectedLog] = useState<LogItem | null>(null);

    const handleFilterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/activity', {
            user_id: userId,
            event,
            subject_type: subjectType,
            start_date: startDate,
            end_date: endDate,
        }, { preserveState: true });
    };

    const handleClearFilters = () => {
        setUserId('');
        setEvent('');
        setSubjectType('');
        setStartDate('');
        setEndDate('');
        router.get('/admin/activity');
    };

    const handleExport = () => {
        window.open(`/admin/activity/export?user_id=${userId}&event=${event}&subject_type=${subjectType}&start_date=${startDate}&end_date=${endDate}`, '_blank');
    };

    const openDetails = (log: LogItem) => {
        setSelectedLog(log);
        setDetailOpen(true);
    };

    const parseJsonValues = (val: any) => {
        if (!val) return 'None';
        if (typeof val === 'string') {
            try {
                return JSON.stringify(JSON.parse(val), null, 2);
            } catch {
                return val;
            }
        }
        return JSON.stringify(val, null, 2);
    };

    return (
        <>
            <Head title="Activity Logs" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">Activity Logs</h1>
                        <p className="text-muted-foreground text-sm">
                            Audit trail of system administrative events and customer account activity.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" onClick={handleExport} className="cursor-pointer">
                            <Download className="mr-2 h-4 w-4 text-primary" />
                            Export CSV
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <Card className="border border-border">
                    <CardContent className="p-4">
                        <form onSubmit={handleFilterSubmit} className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 items-end">
                            <div className="grid gap-2">
                                <Label htmlFor="user_id">Actor User ID</Label>
                                <Input
                                    id="user_id"
                                    placeholder="User ID..."
                                    value={userId}
                                    onChange={(e) => setUserId(e.target.value)}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="event">Event Name</Label>
                                <Input
                                    id="event"
                                    placeholder="e.g. user.suspended"
                                    value={event}
                                    onChange={(e) => setEvent(e.target.value)}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="subject_type">Subject Type</Label>
                                <Input
                                    id="subject_type"
                                    placeholder="e.g. App\Models\User"
                                    value={subjectType}
                                    onChange={(e) => setSubjectType(e.target.value)}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="start_date">Start Date</Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="end_date">End Date</Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                />
                            </div>

                            <div className="flex gap-2 md:col-span-5 justify-end">
                                <Button type="submit" className="cursor-pointer">Filter</Button>
                                <Button type="button" variant="outline" onClick={handleClearFilters} className="cursor-pointer">Clear</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card className="border border-border">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="bg-muted/40 text-xs font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                                        <th className="p-4">ID</th>
                                        <th className="p-4">Actor</th>
                                        <th className="p-4">Event</th>
                                        <th className="p-4">Description</th>
                                        <th className="p-4">IP Address</th>
                                        <th className="p-4">Timestamp</th>
                                        <th className="p-4 text-right">Details</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border text-sm">
                                    {logs.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="p-8 text-center text-muted-foreground">
                                                No activity logs found.
                                            </td>
                                        </tr>
                                    ) : (
                                        logs.data.map((log) => (
                                            <tr key={log.id} className="hover:bg-muted/10 transition-colors">
                                                <td className="p-4 font-mono text-xs">{log.id}</td>
                                                <td className="p-4">
                                                    {log.user ? (
                                                        <div className="flex flex-col">
                                                            <span className="font-semibold text-foreground">{log.user.name}</span>
                                                            <span className="text-xs text-muted-foreground font-mono">{log.user.email}</span>
                                                        </div>
                                                    ) : (
                                                        <Badge variant="secondary" className="text-[10px]">SYSTEM</Badge>
                                                    )}
                                                </td>
                                                <td className="p-4">
                                                    <Badge className="font-mono text-[10px] bg-primary/10 text-primary border-none hover:bg-primary/15">
                                                        {log.event}
                                                    </Badge>
                                                </td>
                                                <td className="p-4 text-muted-foreground max-w-xs truncate">
                                                    {log.description || 'No description'}
                                                </td>
                                                <td className="p-4 font-mono text-xs text-muted-foreground">
                                                    {log.ip_address || '—'}
                                                </td>
                                                <td className="p-4 text-muted-foreground">
                                                    {new Date(log.created_at).toLocaleString()}
                                                </td>
                                                <td className="p-4 text-right">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => openDetails(log)}
                                                        className="h-8 text-xs cursor-pointer"
                                                    >
                                                        <Eye className="h-3.5 w-3.5 mr-1" />
                                                        View Diff
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
                                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                    <p className="text-sm text-muted-foreground">
                                        Showing page <span className="font-semibold text-foreground">{logs.current_page}</span> of{' '}
                                        <span className="font-semibold text-foreground">{logs.last_page}</span> ({logs.total} total logs)
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

                {/* Details Dialog */}
                <Dialog open={detailOpen} onOpenChange={setDetailOpen}>
                    <DialogContent className="max-w-2xl max-h-[85vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Activity Log #{selectedLog?.id}</DialogTitle>
                            <DialogDescription>
                                Event: <strong className="font-mono text-primary">{selectedLog?.event}</strong><br />
                                Time: {selectedLog && new Date(selectedLog.created_at).toLocaleString()}
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4 py-2">
                            {selectedLog?.description && (
                                <div className="p-3 bg-muted rounded-md text-sm">
                                    <strong>Description:</strong> {selectedLog.description}
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-4 text-xs font-mono">
                                <div>
                                    <span className="text-muted-foreground block mb-1 font-sans font-bold">Metadata:</span>
                                    <div className="p-2.5 bg-muted/50 rounded-md border border-border">
                                        <strong>IP Address:</strong> {selectedLog?.ip_address || '—'}<br />
                                        <strong>User Agent:</strong> {selectedLog?.user_agent || '—'}<br />
                                        <strong>Subject model:</strong> {selectedLog?.subject_type || 'None'} ({selectedLog?.subject_id || 'N/A'})
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span className="text-rose-500 font-semibold block mb-1 text-sm">Old Values (Before)</span>
                                    <pre className="p-3 bg-rose-50 dark:bg-rose-950/20 text-rose-900 dark:text-rose-200 text-xs font-mono rounded-md border border-rose-200/50 dark:border-rose-900/50 overflow-x-auto max-h-[250px]">
                                        {parseJsonValues(selectedLog?.old_values)}
                                    </pre>
                                </div>
                                <div>
                                    <span className="text-emerald-500 font-semibold block mb-1 text-sm">New Values (After)</span>
                                    <pre className="p-3 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-900 dark:text-emerald-200 text-xs font-mono rounded-md border border-emerald-200/50 dark:border-emerald-900/50 overflow-x-auto max-h-[250px]">
                                        {parseJsonValues(selectedLog?.new_values)}
                                    </pre>
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button onClick={() => setDetailOpen(false)}>Close</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

ActivityLogsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Activity Logs',
            href: '/admin/activity',
        },
    ],
};
