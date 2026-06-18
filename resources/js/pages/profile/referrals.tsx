import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
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
    Gift,
    Copy,
    Check,
    Users,
    UserCheck,
    Hourglass,
    Coins,
} from 'lucide-react';
import { toast } from 'sonner';

interface ReferralStat {
    total: number;
    pending: number;
    converted: number;
    rewarded: number;
}

interface ReferralHistoryItem {
    id: number;
    referred_name: string;
    referred_email: string;
    status: 'pending' | 'converted' | 'rewarded';
    reward_type: 'discount' | 'credit' | 'none';
    reward_value: number;
    created_at: string;
}

interface Props {
    referral_code: string | null;
    stats: ReferralStat;
    history: ReferralHistoryItem[];
}

export default function Referrals({ referral_code, stats, history }: Props) {
    const [copied, setCopied] = useState(false);

    const getReferralUrl = () => {
        if (!referral_code) return '';
        if (typeof window !== 'undefined') {
            return `${window.location.origin}/register?ref=${referral_code}`;
        }
        return `/register?ref=${referral_code}`;
    };

    const handleCopy = () => {
        const url = getReferralUrl();
        if (!url) return;

        navigator.clipboard.writeText(url);
        setCopied(true);
        toast.success('Referral link copied to clipboard!');
        setTimeout(() => setCopied(false), 2000);
    };

    const formatReward = (type: string, value: number) => {
        if (type === 'none' || !value) return 'None';
        if (type === 'credit') {
            return `$${value.toFixed(2)} Credit`;
        }
        if (type === 'discount') {
            return `${value.toFixed(0)}% Discount`;
        }
        return `${value} ${type}`;
    };

    return (
        <>
            <Head title="Referral Program" />

            <div className="mx-auto max-w-5xl px-4 py-8 space-y-6">
                <Heading
                    title="Referral Program"
                    description="Invite your friends and earn rewards when they subscribe."
                />

                {/* Stats Section */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Card className="border border-border">
                        <CardHeader className="p-4 flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                Total Referrals
                            </CardTitle>
                            <Users className="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent className="p-4 pt-0">
                            <div className="text-2xl font-extrabold">{stats.total}</div>
                        </CardContent>
                    </Card>

                    <Card className="border border-border">
                        <CardHeader className="p-4 flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                Pending Signups
                            </CardTitle>
                            <Hourglass className="h-4 w-4 text-amber-500" />
                        </CardHeader>
                        <CardContent className="p-4 pt-0">
                            <div className="text-2xl font-extrabold">{stats.pending}</div>
                        </CardContent>
                    </Card>

                    <Card className="border border-border">
                        <CardHeader className="p-4 flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                Converted Users
                            </CardTitle>
                            <UserCheck className="h-4 w-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent className="p-4 pt-0">
                            <div className="text-2xl font-extrabold">{stats.converted}</div>
                        </CardContent>
                    </Card>

                    <Card className="border border-border">
                        <CardHeader className="p-4 flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                Rewarded Referrals
                            </CardTitle>
                            <Coins className="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent className="p-4 pt-0">
                            <div className="text-2xl font-extrabold">{stats.rewarded}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Referral Link Sharing */}
                {referral_code && (
                    <Card className="border border-border relative overflow-hidden bg-primary/5 border-primary/20">
                        <CardHeader>
                            <CardTitle className="text-lg font-bold flex items-center gap-2">
                                <Gift className="h-5 w-5 text-primary" />
                                Share Your Link & Earn Rewards
                            </CardTitle>
                            <CardDescription>
                                Copy your unique referral code or link below. When people sign up using your link and subscribe, you both get bonuses!
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex gap-2">
                                <input
                                    type="text"
                                    readOnly
                                    value={getReferralUrl()}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 font-mono"
                                />
                                <Button
                                    onClick={handleCopy}
                                    className="bg-primary text-primary-foreground hover:bg-primary/90 cursor-pointer"
                                >
                                    {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                                    <span className="ml-2 hidden sm:inline">Copy Link</span>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Referral History */}
                <Card className="border border-border">
                    <CardHeader>
                        <CardTitle className="text-lg font-bold">Referral History</CardTitle>
                        <CardDescription>
                            See who has signed up using your link and the status of your rewards.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {history.length === 0 ? (
                            <div className="text-center py-8 text-muted-foreground text-sm">
                                You haven't referred anyone yet. Share your link to start earning!
                            </div>
                        ) : (
                            <div className="overflow-x-auto rounded-lg border border-border">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-muted/40 text-xs font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                                            <th className="p-4">Date</th>
                                            <th className="p-4">User</th>
                                            <th className="p-4">Email</th>
                                            <th className="p-4">Status</th>
                                            <th className="p-4">Earned Reward</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border text-sm">
                                        {history.map((item) => (
                                            <tr key={item.id} className="hover:bg-muted/10 transition-colors">
                                                <td className="p-4 text-muted-foreground">{item.created_at}</td>
                                                <td className="p-4 font-semibold text-foreground">{item.referred_name}</td>
                                                <td className="p-4 font-mono text-muted-foreground">{item.referred_email}</td>
                                                <td className="p-4">
                                                    <Badge
                                                        variant="outline"
                                                        className={`text-[10px] font-bold uppercase ${
                                                            item.status === 'rewarded'
                                                                ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
                                                                : item.status === 'converted'
                                                                ? 'bg-blue-500/10 text-blue-500 border-blue-500/20'
                                                                : 'bg-amber-500/10 text-amber-500 border-amber-500/20'
                                                        }`}
                                                    >
                                                        {item.status}
                                                    </Badge>
                                                </td>
                                                <td className="p-4">
                                                    {item.status === 'rewarded' ? (
                                                        <span className="font-semibold text-emerald-600 dark:text-emerald-400">
                                                            {formatReward(item.reward_type, item.reward_value)}
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted-foreground">—</span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Referrals.layout = {
    breadcrumbs: [
        {
            title: 'Referrals',
            href: '/profile/referrals',
        },
    ],
};
