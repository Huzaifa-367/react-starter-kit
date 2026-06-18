import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { RefreshCw, Trash2, ArrowLeft, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface UserItem {
    id: number;
    name: string;
    email: string;
    deleted_at: string;
    roles: Array<{ id: number; name: string }>;
}

interface Props {
    users: {
        data: UserItem[];
        links: any[];
        total: number;
        current_page: number;
        last_page: number;
    };
}

export default function TrashedUsers({ users }: Props) {
    const [actionLoading, setActionLoading] = useState<number | null>(null);

    const handleRestore = (user: UserItem) => {
        setActionLoading(user.id);
        router.post(`/admin/users/${user.id}/restore`, {}, {
            onSuccess: () => {
                toast.success('User account restored successfully.');
            },
            onFinish: () => setActionLoading(null),
        });
    };

    return (
        <>
            <Head title="Trashed Users" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div className="flex items-center gap-3">
                        <Link href="/admin/users">
                            <Button variant="ghost" size="icon" className="h-8 w-8 cursor-pointer">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-foreground">Trashed Accounts</h1>
                            <p className="text-muted-foreground text-sm">
                                Soft-deleted user accounts awaiting permanent deletion.
                            </p>
                        </div>
                    </div>
                </div>

                <Card className="border border-border">
                    <CardHeader>
                        <CardTitle className="text-base font-bold">Soft Deleted Customers</CardTitle>
                        <CardDescription>
                            Restore accounts to restore access immediately.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr className="bg-muted/40 font-bold text-muted-foreground border-b border-border">
                                        <th className="p-4">User</th>
                                        <th className="p-4">Roles</th>
                                        <th className="p-4">Deleted At</th>
                                        <th className="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {users.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={4} className="p-8 text-center text-muted-foreground">
                                                No trashed accounts found.
                                            </td>
                                        </tr>
                                    ) : (
                                        users.data.map((user) => (
                                            <tr key={user.id} className="hover:bg-muted/10 transition-colors">
                                                <td className="p-4">
                                                    <div className="flex flex-col">
                                                        <span className="font-semibold text-foreground">{user.name}</span>
                                                        <span className="text-xs text-muted-foreground font-mono">{user.email}</span>
                                                    </div>
                                                </td>
                                                <td className="p-4">
                                                    <div className="flex flex-wrap gap-1">
                                                        {user.roles.map((r) => (
                                                            <Badge key={r.id} variant="secondary" className="text-[10px] font-semibold">
                                                                {r.name}
                                                            </Badge>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="p-4 text-muted-foreground">
                                                    {new Date(user.deleted_at).toLocaleString()}
                                                </td>
                                                <td className="p-4 text-right">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleRestore(user)}
                                                        disabled={actionLoading === user.id}
                                                        className="border-emerald-200 text-emerald-600 dark:border-emerald-900/50 hover:bg-emerald-50 dark:hover:bg-emerald-950/20 cursor-pointer"
                                                    >
                                                        {actionLoading === user.id ? (
                                                            <Loader2 className="h-3 w-3 animate-spin mr-1" />
                                                        ) : (
                                                            <RefreshCw className="h-3 w-3 mr-1" />
                                                        )}
                                                        Restore Account
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {users.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-border px-4 py-3 sm:px-6">
                                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                    <p className="text-sm text-muted-foreground">
                                        Showing page <span className="font-semibold text-foreground">{users.current_page}</span> of{' '}
                                        <span className="font-semibold text-foreground">{users.last_page}</span> ({users.total} total accounts)
                                    </p>
                                    <nav className="isolate inline-flex -space-x-px rounded-md shadow-xs" aria-label="Pagination">
                                        {users.links.map((link: any, idx: number) => (
                                            <Link
                                                key={idx}
                                                href={link.url || '#'}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                className={`relative inline-flex items-center px-4 py-2 text-sm font-semibold border border-border focus:z-20 ${
                                                    link.active
                                                        ? 'z-10 bg-primary text-primary-foreground focus-visible:outline-2'
                                                        : 'text-foreground hover:bg-accent bg-background'
                                                } ${!link.url ? 'pointer-events-none opacity-50' : ''} ${
                                                    idx === 0 ? 'rounded-l-md' : idx === users.links.length - 1 ? 'rounded-r-md' : ''
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

TrashedUsers.layout = {
    breadcrumbs: [
        { title: 'Users Manager', href: '/admin/users' },
        { title: 'Trashed Accounts', href: '/admin/users/trashed' },
    ],
};
