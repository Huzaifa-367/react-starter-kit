import React from 'react';
import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ShieldCheck, ShieldAlert, Monitor, Smartphone, Globe } from 'lucide-react';

interface LoginHistoryItem {
    id: number;
    ip_address: string;
    user_agent: string | null;
    login_at: string;
    status: 'success' | 'failed' | 'blocked';
    failure_reason: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    history: {
        data: LoginHistoryItem[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}

export default function LoginHistory({ history }: Props) {
    const parseUserAgent = (uaString: string | null) => {
        if (!uaString) return { browser: 'Unknown', platform: 'Unknown' };

        let browser = 'Unknown Browser';
        let platform = 'Unknown OS';

        if (/Chrome/i.test(uaString)) browser = 'Chrome';
        else if (/Safari/i.test(uaString)) browser = 'Safari';
        else if (/Firefox/i.test(uaString)) browser = 'Firefox';
        else if (/Opera|OPR/i.test(uaString)) browser = 'Opera';
        else if (/MSIE|Trident/i.test(uaString)) browser = 'Internet Explorer';

        if (/Windows/i.test(uaString)) platform = 'Windows';
        else if (/Macintosh|Mac OS X/i.test(uaString)) platform = 'macOS';
        else if (/Linux/i.test(uaString)) platform = 'Linux';
        else if (/iPhone|iPad|iPod/i.test(uaString)) platform = 'iOS';
        else if (/Android/i.test(uaString)) platform = 'Android';

        return { browser, platform };
    };

    return (
        <>
            <Head title="Login History" />

            <div className="mx-auto max-w-5xl px-4 py-8 space-y-6">
                <Heading
                    title="Login History"
                    description="Review details of recent login attempts to monitor security."
                />

                <Card className="border border-border">
                    <CardHeader>
                        <CardTitle className="text-lg font-bold">Recent Sign-In Attempts</CardTitle>
                        <CardDescription>
                            A log of successful, failed, or blocked authentication attempts.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="bg-muted/40 text-xs font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                                        <th className="p-4">Time</th>
                                        <th className="p-4">Status</th>
                                        <th className="p-4">Device / OS</th>
                                        <th className="p-4">Browser</th>
                                        <th className="p-4">IP Address</th>
                                        <th className="p-4">Details</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border text-sm">
                                    {history.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="p-8 text-center text-muted-foreground">
                                                No login history found.
                                            </td>
                                        </tr>
                                    ) : (
                                        history.data.map((item) => {
                                            const { browser, platform } = parseUserAgent(item.user_agent);
                                            const isSuccess = item.status === 'success';

                                            return (
                                                <tr key={item.id} className="hover:bg-muted/10 transition-colors">
                                                    <td className="p-4 text-muted-foreground">
                                                        {new Date(item.login_at).toLocaleString()}
                                                    </td>
                                                    <td className="p-4">
                                                        <Badge
                                                            variant="outline"
                                                            className={`text-[10px] font-bold uppercase ${
                                                                isSuccess
                                                                    ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
                                                                    : 'bg-rose-500/10 text-rose-500 border-rose-500/20'
                                                            }`}
                                                        >
                                                            {isSuccess ? (
                                                                <span className="flex items-center gap-1">
                                                                    <ShieldCheck className="h-3 w-3" /> Success
                                                                </span>
                                                            ) : (
                                                                <span className="flex items-center gap-1">
                                                                    <ShieldAlert className="h-3 w-3" /> {item.status}
                                                                </span>
                                                            )}
                                                        </Badge>
                                                    </td>
                                                    <td className="p-4">
                                                        <span className="flex items-center gap-2">
                                                            {/iOS|Android/i.test(platform) ? (
                                                                <Smartphone className="h-4 w-4 text-muted-foreground" />
                                                            ) : (
                                                                <Monitor className="h-4 w-4 text-muted-foreground" />
                                                            )}
                                                            {platform}
                                                        </span>
                                                    </td>
                                                    <td className="p-4 text-muted-foreground">{browser}</td>
                                                    <td className="p-4 font-mono">{item.ip_address}</td>
                                                    <td className="p-4 text-muted-foreground">
                                                        {item.failure_reason || '—'}
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination Footer */}
                        {history.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-border px-4 py-3 sm:px-6">
                                <div className="flex flex-1 justify-between sm:hidden">
                                    <Link
                                        href={history.prev_page_url || '#'}
                                        className={`relative inline-flex items-center rounded-md border border-border bg-background px-4 py-2 text-sm font-medium text-muted-foreground hover:bg-accent ${
                                            !history.prev_page_url ? 'pointer-events-none opacity-50' : ''
                                        }`}
                                    >
                                        Previous
                                    </Link>
                                    <Link
                                        href={history.next_page_url || '#'}
                                        className={`relative ml-3 inline-flex items-center rounded-md border border-border bg-background px-4 py-2 text-sm font-medium text-muted-foreground hover:bg-accent ${
                                            !history.next_page_url ? 'pointer-events-none opacity-50' : ''
                                        }`}
                                    >
                                        Next
                                    </Link>
                                </div>
                                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            Showing page <span className="font-semibold text-foreground">{history.current_page}</span> of{' '}
                                            <span className="font-semibold text-foreground">{history.last_page}</span> ({history.total} total attempts)
                                        </p>
                                    </div>
                                    <div>
                                        <nav className="isolate inline-flex -space-x-px rounded-md shadow-xs" aria-label="Pagination">
                                            {history.links.map((link, idx) => (
                                                <Link
                                                    key={idx}
                                                    href={link.url || '#'}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                    className={`relative inline-flex items-center px-4 py-2 text-sm font-semibold border border-border focus:z-20 ${
                                                        link.active
                                                            ? 'z-10 bg-primary text-primary-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary'
                                                            : 'text-foreground hover:bg-accent bg-background'
                                                    } ${!link.url ? 'pointer-events-none opacity-50' : ''} ${
                                                        idx === 0 ? 'rounded-l-md' : idx === history.links.length - 1 ? 'rounded-r-md' : ''
                                                    }`}
                                                />
                                            ))}
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

LoginHistory.layout = {
    breadcrumbs: [
        {
            title: 'Login History',
            href: '/profile/login-history',
        },
    ],
};
