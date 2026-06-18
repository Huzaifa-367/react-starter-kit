import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Terminal, Download, Eye, Trash2, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface LogFile {
    filename: string;
    size: string;
    modified_at: string;
}

interface Props {
    logFiles: LogFile[];
}

export default function LogsIndex({ logFiles }: Props) {
    const [actionLoading, setActionLoading] = useState<string | null>(null);

    const handleClear = (filename: string) => {
        if (!confirm(`Are you sure you want to completely empty the logs in "${filename}"? This action is restricted to Super Admins.`)) return;
        setActionLoading(`clear-${filename}`);
        router.delete(`/admin/logs/${filename}`, {
            onSuccess: () => toast.success('Log file content cleared.'),
            onFinish: () => setActionLoading(null),
        });
    };

    const handleDownload = (filename: string) => {
        window.open(`/admin/logs/${filename}/download`, '_blank');
    };

    return (
        <>
            <Head title="System logs Viewer" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            Log Files Viewer
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Inspect local application storage log files. Level tails are limited to the last 500 lines.
                        </p>
                    </div>
                </div>

                {/* List Table */}
                <Card className="border border-border">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="bg-muted/40 text-xs font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                                        <th className="p-4">File Name</th>
                                        <th className="p-4">File Size</th>
                                        <th className="p-4">Last Modified</th>
                                        <th className="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border text-sm font-mono">
                                    {logFiles.length === 0 ? (
                                        <tr>
                                            <td colSpan={4} className="p-8 text-center text-muted-foreground font-sans">
                                                No log files detected in the storage/logs folder.
                                            </td>
                                        </tr>
                                    ) : (
                                        logFiles.map((file) => (
                                            <tr key={file.filename} className="hover:bg-muted/10 transition-colors">
                                                <td className="p-4 font-semibold text-foreground flex items-center gap-2">
                                                    <Terminal className="h-4 w-4 text-primary shrink-0" />
                                                    {file.filename}
                                                </td>
                                                <td className="p-4 text-muted-foreground">
                                                    {file.size}
                                                </td>
                                                <td className="p-4 text-muted-foreground">
                                                    {file.modified_at}
                                                </td>
                                                <td className="p-4 text-right space-x-1.5 whitespace-nowrap font-sans">
                                                    <Link href={`/admin/logs/${file.filename}`}>
                                                        <Button variant="ghost" size="sm" className="h-8 text-xs cursor-pointer">
                                                            <Eye className="h-3.5 w-3.5 mr-1" />
                                                            Inspect Tail
                                                        </Button>
                                                    </Link>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleDownload(file.filename)}
                                                        className="h-8 text-xs cursor-pointer"
                                                    >
                                                        <Download className="h-3.5 w-3.5 mr-1 text-primary" />
                                                        Download
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => handleClear(file.filename)}
                                                        disabled={actionLoading === `clear-${file.filename}`}
                                                        className="h-8 w-8 hover:bg-rose-500/10 hover:text-rose-500 cursor-pointer"
                                                    >
                                                        {actionLoading === `clear-${file.filename}` ? (
                                                            <Loader2 className="h-4 w-4 animate-spin text-rose-500" />
                                                        ) : (
                                                            <Trash2 className="h-4 w-4 text-rose-500" />
                                                        )}
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))
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

LogsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Logs Viewer',
            href: '/admin/logs',
        },
    ],
};
