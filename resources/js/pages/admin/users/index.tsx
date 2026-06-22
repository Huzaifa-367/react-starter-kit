import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Search,
    UserX,
    UserCheck,
    Shield,
    CreditCard,
    Zap,
    Loader2,
    Trash2,
} from 'lucide-react';
import { toast } from 'sonner';

interface UserItem {
    id: number;
    name: string;
    email: string;
    is_suspended: boolean;
    suspended_reason: string | null;
    roles: Array<{ id: number; name: string }>;
    active_subscription: {
        id: number;
        status: string;
        plan: {
            id: number;
            name: string;
        };
    } | null;
    created_at: string;
}

interface RoleItem {
    id: number;
    name: string;
}

interface PlanItem {
    id: number;
    name: string;
}

interface Props {
    users: {
        data: UserItem[];
        links: any[];
        total: number;
        current_page: number;
        last_page: number;
    };
    roles: RoleItem[];
    plans: PlanItem[];
    filters: {
        search?: string;
        role?: string;
        status?: string;
        plan?: string;
        suspended?: string;
    };
}

export default function UsersIndex({ users, roles, plans, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedRole, setSelectedRole] = useState(filters.role || '');
    const [selectedPlan, setSelectedPlan] = useState(filters.plan || '');
    const [selectedSuspended, setSelectedSuspended] = useState(filters.suspended || '');

    // Modal states
    const [suspendOpen, setSuspendOpen] = useState(false);
    const [suspendReason, setSuspendReason] = useState('');
    const [targetUser, setTargetUser] = useState<UserItem | null>(null);
    const [actionLoading, setActionLoading] = useState<string | null>(null);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/users', {
            search,
            role: selectedRole,
            plan: selectedPlan,
            suspended: selectedSuspended,
        }, { preserveState: true });
    };

    const handleClearFilters = () => {
        setSearch('');
        setSelectedRole('');
        setSelectedPlan('');
        setSelectedSuspended('');
        router.get('/admin/users');
    };

    const handleImpersonate = (user: UserItem) => {
        if (!confirm(`Do you want to impersonate ${user.name}? This will log you in as them.`)) return;
        setActionLoading(`impersonate-${user.id}`);
        router.post(`/admin/users/${user.id}/impersonate`, {}, {
            onSuccess: () => toast.success(`Now impersonating ${user.name}`),
            onFinish: () => setActionLoading(null),
        });
    };

    const handleSuspend = () => {
        if (!targetUser) return;
        setActionLoading('suspend');
        router.post(`/admin/users/${targetUser.id}/suspend`, {
            reason: suspendReason,
        }, {
            onSuccess: () => {
                toast.success('User suspended successfully.');
                setSuspendOpen(false);
                setSuspendReason('');
            },
            onFinish: () => setActionLoading(null),
        });
    };

    const handleUnsuspend = (user: UserItem) => {
        setActionLoading(`unsuspend-${user.id}`);
        router.post(`/admin/users/${user.id}/unsuspend`, {}, {
            onSuccess: () => toast.success('User account unsuspended.'),
            onFinish: () => setActionLoading(null),
        });
    };

    const handleDelete = (user: UserItem) => {
        if (!confirm(`Are you sure you want to soft delete ${user.name}?`)) return;
        setActionLoading(`delete-${user.id}`);
        router.delete(`/admin/users/${user.id}`, {
            onSuccess: () => toast.success('User soft deleted.'),
            onFinish: () => setActionLoading(null),
        });
    };

    return (
        <>
            <Head title="Users Manager" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">Users Manager</h1>
                        <p className="text-muted-foreground text-sm">
                            Manage all registration accounts, permissions, roles, and SaaS subscriptions.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link href="/admin/users/trashed">
                            <Button variant="outline" size="sm">
                                <Trash2 className="mr-2 h-4 w-4 text-rose-500" />
                                View Trashed Users
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Filter and Search Bar */}
                <Card className="border border-border">
                    <CardContent className="p-4">
                        <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 items-end">
                            <div className="grid gap-2 col-span-1 sm:col-span-2 md:col-span-1">
                                <Label htmlFor="search">Search</Label>
                                <div className="relative">
                                    <Search className="absolute left-2.5 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        placeholder="Name, email..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-8"
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="role">Role</Label>
                                <select
                                    id="role"
                                    value={selectedRole}
                                    onChange={(e) => setSelectedRole(e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">All Roles</option>
                                    {roles.map((r) => (
                                        <option key={r.id} value={r.name}>{r.name}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="plan">Plan</Label>
                                <select
                                    id="plan"
                                    value={selectedPlan}
                                    onChange={(e) => setSelectedPlan(e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">All Plans</option>
                                    {plans.map((p) => (
                                        <option key={p.id} value={p.id}>{p.name}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="suspended">Status</Label>
                                <select
                                    id="suspended"
                                    value={selectedSuspended}
                                    onChange={(e) => setSelectedSuspended(e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">All Accounts</option>
                                    <option value="false">Active Only</option>
                                    <option value="true">Suspended Only</option>
                                </select>
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" className="flex-1 cursor-pointer">Filter</Button>
                                <Button type="button" variant="outline" onClick={handleClearFilters} className="cursor-pointer">Clear</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Users List Card */}
                <Card className="border border-border">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="bg-muted/40 text-xs font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                                        <th className="p-4">User</th>
                                        <th className="p-4">Roles</th>
                                        <th className="p-4">SaaS Subscription</th>
                                        <th className="p-4">Joined Date</th>
                                        <th className="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border text-sm">
                                    {users.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="p-8 text-center text-muted-foreground">
                                                No users found matching filters.
                                            </td>
                                        </tr>
                                    ) : (
                                        users.data.map((user) => (
                                            <tr key={user.id} className="hover:bg-muted/10 transition-colors">
                                                <td className="p-4">
                                                    <div className="flex flex-col">
                                                        <span className="font-semibold text-foreground flex items-center gap-1.5">
                                                            <Link href={`/admin/users/${user.id}`} className="hover:underline text-primary">
                                                                {user.name}
                                                            </Link>
                                                            {user.is_suspended && (
                                                                <Badge variant="destructive" className="text-[9px] py-0 px-1 font-bold">
                                                                    SUSPENDED
                                                                </Badge>
                                                            )}
                                                        </span>
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
                                                <td className="p-4">
                                                    {user.active_subscription ? (
                                                        <div className="flex flex-col">
                                                            <span className="font-medium">{user.active_subscription.plan.name}</span>
                                                            <span className="text-xs text-emerald-600 font-semibold uppercase">
                                                                {user.active_subscription.status}
                                                            </span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted-foreground text-xs">—</span>
                                                    )}
                                                </td>
                                                <td className="p-4 text-muted-foreground">
                                                    {new Date(user.created_at).toLocaleDateString()}
                                                </td>
                                                <td className="p-4 text-right space-x-1.5 whitespace-nowrap">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            e.stopPropagation();
                                                            handleImpersonate(user);
                                                        }}
                                                        disabled={actionLoading === `impersonate-${user.id}`}
                                                        className="h-8 text-xs cursor-pointer"
                                                    >
                                                        {actionLoading === `impersonate-${user.id}` ? (
                                                            <Loader2 className="h-3 w-3 animate-spin mr-1" />
                                                        ) : (
                                                            <Zap className="h-3 w-3 mr-1 text-amber-500" />
                                                        )}
                                                        Impersonate
                                                    </Button>

                                                    {user.is_suspended ? (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleUnsuspend(user)}
                                                            disabled={actionLoading === `unsuspend-${user.id}`}
                                                            className="h-8 text-xs border-emerald-200 text-emerald-600 dark:border-emerald-900/50 hover:bg-emerald-50 dark:hover:bg-emerald-950/20 cursor-pointer"
                                                        >
                                                            <UserCheck className="h-3 w-3 mr-1" />
                                                            Unsuspend
                                                        </Button>
                                                    ) : (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => {
                                                                setTargetUser(user);
                                                                setSuspendOpen(true);
                                                            }}
                                                            className="h-8 text-xs border-rose-200 text-rose-600 dark:border-rose-900/50 hover:bg-rose-50 dark:hover:bg-rose-950/20 cursor-pointer"
                                                        >
                                                            <UserX className="h-3 w-3 mr-1" />
                                                            Suspend
                                                        </Button>
                                                    )}

                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            e.stopPropagation();
                                                            handleDelete(user);
                                                        }}
                                                        disabled={actionLoading === `delete-${user.id}`}
                                                        className="h-8 w-8 hover:bg-rose-500/10 hover:text-rose-500 cursor-pointer"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
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
                                        <span className="font-semibold text-foreground">{users.last_page}</span> ({users.total} total customers)
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

                {/* Suspend Confirmation Dialog */}
                <Dialog open={suspendOpen} onOpenChange={setSuspendOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Suspend User Account</DialogTitle>
                            <DialogDescription>
                                Please provide a reason for suspending {targetUser?.name}'s account. This reason will be logged and emailed to them.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4 py-2">
                            <div className="grid gap-2">
                                <Label htmlFor="reason">Suspension Reason</Label>
                                <Input
                                    id="reason"
                                    placeholder="Violations of ToS, unpaid fees..."
                                    value={suspendReason}
                                    onChange={(e) => setSuspendReason(e.target.value)}
                                    required
                                />
                            </div>
                        </div>

                        <DialogFooter>
                            <Button variant="outline" onClick={() => setSuspendOpen(false)}>Cancel</Button>
                            <Button
                                onClick={handleSuspend}
                                disabled={actionLoading === 'suspend' || !suspendReason}
                                className="bg-rose-600 text-white hover:bg-rose-700"
                            >
                                {actionLoading === 'suspend' && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                Suspend Account
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Users Manager',
            href: '/admin/users',
        },
    ],
};
