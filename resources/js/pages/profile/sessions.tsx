import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Monitor,
    Smartphone,
    Chrome,
    Compass,
    Globe,
    LogOut,
    Loader2,
    ShieldAlert,
} from 'lucide-react';
import { toast } from 'sonner';

interface SessionItem {
    id: string;
    ip_address: string;
    is_current_device: boolean;
    browser: string;
    platform: string;
    last_active: string;
}

interface Props {
    sessions: SessionItem[];
}

export default function ActiveSessions({ sessions }: Props) {
    const [actionLoading, setActionLoading] = useState<string | null>(null);

    const getDeviceIcon = (platform: string) => {
        const p = platform.toLowerCase();
        if (p.includes('ios') || p.includes('android')) {
            return <Smartphone className="h-5 w-5 text-muted-foreground" />;
        }
        return <Monitor className="h-5 w-5 text-muted-foreground" />;
    };

    const getBrowserIcon = (browser: string) => {
        const b = browser.toLowerCase();
        if (b.includes('chrome')) {
            return <Chrome className="h-4 w-4 text-primary" />;
        }
        if (b.includes('safari')) {
            return <Compass className="h-4 w-4 text-primary" />;
        }
        return <Globe className="h-4 w-4 text-primary" />;
    };

    const handleTerminate = (id: string) => {
        if (!confirm('Are you sure you want to log out of this session?')) return;
        setActionLoading(id);
        router.delete(`/profile/sessions/${id}`, {
            onSuccess: () => {
                toast.success('Session terminated.');
            },
            onError: (err) => {
                toast.error('Failed to terminate session.');
            },
            onFinish: () => setActionLoading(null),
        });
    };

    const handleTerminateOthers = () => {
        if (!confirm('Are you sure you want to log out of all other active sessions across all devices?')) return;
        setActionLoading('others');
        router.delete('/profile/sessions', {
            onSuccess: () => {
                toast.success('All other sessions terminated.');
            },
            onError: (err) => {
                toast.error('Failed to terminate other sessions.');
            },
            onFinish: () => setActionLoading(null),
        });
    };

    return (
        <>
            <Head title="Active Sessions" />

            <div className="mx-auto max-w-4xl px-4 py-8 space-y-6">
                <Heading
                    title="Active Sessions"
                    description="Manage your active logins on different browsers and devices."
                />

                <Card className="border border-border">
                    <CardHeader className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div className="space-y-1">
                            <CardTitle className="text-lg font-bold">Logged In Devices</CardTitle>
                            <CardDescription>
                                These devices have active access to your account.
                            </CardDescription>
                        </div>
                        {sessions.length > 1 && (
                            <Button
                                variant="destructive"
                                size="sm"
                                onClick={handleTerminateOthers}
                                disabled={actionLoading === 'others'}
                            >
                                {actionLoading === 'others' && (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                )}
                                <LogOut className="mr-2 h-4 w-4" />
                                Log Out Other Devices
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent className="divide-y divide-border">
                        {sessions.map((session) => (
                            <div
                                key={session.id}
                                className="flex items-center justify-between py-4 first:pt-0 last:pb-0 gap-4"
                            >
                                <div className="flex items-start gap-4">
                                    <div className="p-2.5 bg-muted rounded-xl">
                                        {getDeviceIcon(session.platform)}
                                    </div>
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <span className="font-semibold text-sm text-foreground">
                                                {session.platform} ({session.browser})
                                            </span>
                                            {session.is_current_device ? (
                                                <Badge className="bg-emerald-500/10 text-emerald-500 border-emerald-500/20 hover:bg-emerald-500/15 py-0 px-2 text-[10px] uppercase font-bold">
                                                    Current Device
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-[10px] text-muted-foreground border-border py-0 px-2">
                                                    Active
                                                </Badge>
                                            )}
                                        </div>
                                        <div className="flex flex-wrap items-center gap-x-4 text-xs text-muted-foreground">
                                            <span>IP: {session.ip_address}</span>
                                            <span>•</span>
                                            <span>Last active {session.last_active}</span>
                                        </div>
                                    </div>
                                </div>

                                {!session.is_current_device && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="text-muted-foreground hover:text-rose-500 hover:bg-rose-500/10 cursor-pointer"
                                        onClick={() => handleTerminate(session.id)}
                                        disabled={actionLoading === session.id}
                                    >
                                        {actionLoading === session.id ? (
                                            <Loader2 className="h-4 w-4 animate-spin text-rose-500" />
                                        ) : (
                                            <LogOut className="h-4 w-4" />
                                        )}
                                    </Button>
                                )}
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <div className="rounded-xl border border-amber-200/50 bg-amber-500/5 p-4 flex gap-3 items-start dark:border-amber-900/50 dark:bg-amber-950/20">
                    <ShieldAlert className="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
                    <div className="space-y-1">
                        <h5 className="text-sm font-semibold text-amber-800 dark:text-amber-300">
                            Security Recommendation
                        </h5>
                        <p className="text-xs text-amber-700/80 dark:text-amber-400/80 leading-relaxed">
                            If you notice any unfamiliar IP addresses or devices, terminate the session immediately and change your account password.
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}

ActiveSessions.layout = {
    breadcrumbs: [
        {
            title: 'Sessions',
            href: '/profile/sessions',
        },
    ],
};
