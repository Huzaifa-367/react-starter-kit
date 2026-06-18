import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Terminal, Download, ArrowLeft, RefreshCw, Trash2, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface Props {
    filename: string;
    content: string;
}

export default function LogsShow({ filename, content }: Props) {
    const [actionLoading, setActionLoading] = useState(false);
    const [activeLevel, setActiveLevel] = useState<string>('');

    const handleLevelChange = (level: string) => {
        setActiveLevel(level);
        router.get(`/admin/logs/${filename}`, { level }, { preserveState: true });
    };

    const handleRefresh = () => {
        setActionLoading(true);
        router.get(`/admin/logs/${filename}`, { level: activeLevel }, {
            onFinish: () => {
                setActionLoading(false);
                toast.success('Logs refreshed.');
            }
        });
    };

    const handleClear = () => {
        if (!confirm('Are you sure you want to completely empty this log file? This is restricted to Super Admins.')) return;
        setActionLoading(true);
        router.delete(`/admin/logs/${filename}`, {
            onSuccess: () => {
                toast.success('Log file cleared.');
            },
            onFinish: () => setActionLoading(false),
        });
    };

    const handleDownload = () => {
        window.open(`/admin/logs/${filename}/download`, '_blank');
    };

    // Color code log levels
    const colorizeLogs = (text: string) => {
        if (!text) return 'Log file is empty.';
        return text.split('\n').map((line, idx) => {
            let className = 'text-foreground/80';
            if (line.includes('.ERROR:')) className = 'text-rose-500 font-bold';
            else if (line.includes('.CRITICAL:')) className = 'text-rose-600 font-extrabold bg-rose-500/10 px-1 rounded';
            else if (line.includes('.WARNING:')) className = 'text-amber-500 font-semibold';
            else if (line.includes('.INFO:')) className = 'text-sky-500';
            else if (line.includes('.DEBUG:')) className = 'text-muted-foreground';

            return (
                <div key={idx} className={`py-0.5 border-b border-muted-foreground/5 font-mono text-xs ${className}`}>
                    {line}
                </div>
            );
        });
    };

    return (
        <>
            <Head title={`Log: ${filename}`} />

            <div className="flex-1 space-y-6 p-6">
                {/* Header */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div className="flex items-center gap-3">
                        <Link href="/admin/logs">
                            <Button variant="ghost" size="icon" className="h-8 w-8 cursor-pointer">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                                <Terminal className="h-5 w-5 text-primary" />
                                {filename}
                            </h1>
                            <p className="text-muted-foreground text-xs font-mono mt-0.5">
                                storage/logs/{filename}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                        <div className="bg-muted p-1 rounded-md flex gap-1 border border-border">
                            {['', 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'].map((lvl) => (
                                <Button
                                    key={lvl}
                                    variant={activeLevel === lvl ? 'default' : 'ghost'}
                                    size="sm"
                                    onClick={() => handleLevelChange(lvl)}
                                    className="h-8 text-[10px] cursor-pointer"
                                >
                                    {lvl || 'ALL'}
                                </Button>
                            ))}
                        </div>

                        <Button variant="outline" size="sm" onClick={handleRefresh} disabled={actionLoading} className="h-9 cursor-pointer">
                            <RefreshCw className={`h-4 w-4 ${actionLoading ? 'animate-spin' : ''}`} />
                        </Button>

                        <Button variant="outline" size="sm" onClick={handleDownload} className="h-9 cursor-pointer">
                            <Download className="mr-2 h-4 w-4 text-primary" />
                            Download
                        </Button>

                        <Button variant="outline" size="sm" onClick={handleClear} disabled={actionLoading} className="border-rose-200 text-rose-600 dark:border-rose-900/50 hover:bg-rose-50 dark:hover:bg-rose-950/20 h-9 cursor-pointer">
                            <Trash2 className="mr-2 h-4 w-4" />
                            Clear File
                        </Button>
                    </div>
                </div>

                {/* Log Console View */}
                <Card className="border border-border bg-slate-950 dark:bg-black/80 text-foreground overflow-hidden">
                    <CardContent className="p-4 overflow-x-auto max-h-[70vh] overflow-y-auto min-h-[400px]">
                        <pre className="whitespace-pre-wrap break-all leading-normal">
                            {colorizeLogs(content)}
                        </pre>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

LogsShow.layout = {
    breadcrumbs: [
        {
            title: 'Logs Viewer',
            href: '/admin/logs',
        },
    ],
};
