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
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Clock,
    Activity,
    CreditCard,
    MessageSquare,
    Key,
    Phone,
    User,
    CheckCircle,
    XCircle,
    FileText,
    TrendingUp,
    Send,
    Plus,
    Trash2,
    Shield,
    Loader2,
} from 'lucide-react';
import { toast } from 'sonner';

interface RoleItem {
    id: number;
    name: string;
}

interface Props {
    user: any;
    subscriptions: any[];
    activityLogs: any[];
    notificationLogs: any[];
    fcmTokens: any[];
    userNotes: any[];
    credits: any[];
    loginHistory: any[];
    allRoles: RoleItem[];
}

export default function UserShow({
    user,
    subscriptions,
    activityLogs,
    notificationLogs,
    fcmTokens,
    userNotes,
    credits,
    loginHistory,
    allRoles,
}: Props) {
    const [activeTab, setActiveTab] = useState<'overview' | 'activity' | 'notes' | 'notifications' | 'roles'>('overview');
    const [noteContent, setNoteContent] = useState('');
    const [addingNote, setAddingNote] = useState(false);

    // Role assignment state
    const currentRoleIds = (user.roles as RoleItem[]).map((r) => r.id);
    const [selectedRoles, setSelectedRoles] = useState<number[]>(currentRoleIds);
    const [savingRoles, setSavingRoles] = useState(false);

    const toggleRole = (id: number) => {
        setSelectedRoles((prev) =>
            prev.includes(id) ? prev.filter((r) => r !== id) : [...prev, id]
        );
    };

    const handleSaveRoles = () => {
        setSavingRoles(true);
        const roleNames = allRoles
            .filter((r) => selectedRoles.includes(r.id))
            .map((r) => r.name);
        router.post(
            `/admin/users/${user.id}/assign-role`,
            { roles: roleNames },
            {
                onSuccess: () => {
                    import('sonner').then(({ toast }) => toast.success('Roles updated successfully.'));
                },
                onError: () => {
                    import('sonner').then(({ toast }) => toast.error('Failed to update roles.'));
                },
                onFinish: () => setSavingRoles(false),
            }
        );
    };

    const handleAddNote = (e: React.FormEvent) => {
        e.preventDefault();
        if (!noteContent) return;
        setAddingNote(true);
        router.post(`/admin/users/${user.id}/notes`, {
            content: noteContent,
        }, {
            onSuccess: () => {
                toast.success('Note added successfully.');
                setNoteContent('');
            },
            onFinish: () => setAddingNote(false),
        });
    };

    const handleDeleteNote = (noteId: number) => {
        if (!confirm('Are you sure you want to delete this note?')) return;
        router.delete(`/admin/users/${user.id}/notes/${noteId}`, {
            onSuccess: () => toast.success('Note deleted.'),
        });
    };

    return (
        <>
            <Head title={`User Details: ${user.name}`} />

            <div className="flex-1 space-y-6 p-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-border pb-4">
                    <div className="flex items-center gap-3">
                        <div className="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-lg">
                            {user.name.charAt(0)}
                        </div>
                        <div>
                            <div className="flex items-center gap-2 flex-wrap">
                                <h1 className="text-2xl font-bold text-foreground">{user.name}</h1>
                                {user.is_suspended && (
                                    <Badge variant="destructive">SUSPENDED</Badge>
                                )}
                            </div>
                            <p className="text-xs text-muted-foreground font-mono">{user.email}</p>
                        </div>
                    </div>
                    <div>
                        <Link href="/admin/users">
                            <Button variant="outline" size="sm">Back to Users</Button>
                        </Link>
                    </div>
                </div>

                {/* Navigation Tabs */}
                <div className="flex border-b border-border gap-2 overflow-x-auto pb-px">
                    {(['overview', 'activity', 'notes', 'notifications', 'roles'] as const).map((tab) => (
                        <button
                            key={tab}
                            onClick={() => setActiveTab(tab)}
                            className={`px-4 py-2 text-sm font-semibold capitalize border-b-2 transition-all cursor-pointer whitespace-nowrap ${
                                activeTab === tab
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            {tab}
                        </button>
                    ))}
                </div>

                {/* Tab content */}
                <div className="space-y-6">
                    {activeTab === 'overview' && (
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {/* Profile Details */}
                            <Card className="border border-border md:col-span-1">
                                <CardHeader>
                                    <CardTitle className="text-base font-bold">Profile Info</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4 text-sm">
                                    <div className="flex justify-between py-1 border-b border-border">
                                        <span className="text-muted-foreground">ID</span>
                                        <span className="font-semibold">{user.id}</span>
                                    </div>
                                    <div className="flex justify-between py-1 border-b border-border">
                                        <span className="text-muted-foreground">Email Status</span>
                                        <span>
                                            {user.email_verified_at ? (
                                                <Badge className="bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500/10 border-emerald-500/10">Verified</Badge>
                                            ) : (
                                                <Badge variant="destructive">Unverified</Badge>
                                            )}
                                        </span>
                                    </div>
                                    <div className="flex justify-between py-1 border-b border-border">
                                        <span className="text-muted-foreground">Phone Number</span>
                                        <span className="font-semibold">{user.phone_number || 'None'}</span>
                                    </div>
                                    <div className="flex justify-between py-1 border-b border-border">
                                        <span className="text-muted-foreground">Joined At</span>
                                        <span>{new Date(user.created_at).toLocaleDateString()}</span>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Subscriptions History */}
                            <Card className="border border-border md:col-span-2">
                                <CardHeader>
                                    <CardTitle className="text-base font-bold">Subscription Records</CardTitle>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left border-collapse text-sm">
                                            <thead>
                                                <tr className="bg-muted/40 font-bold text-muted-foreground border-b border-border">
                                                    <th className="p-4">Plan Name</th>
                                                    <th className="p-4">Stripe ID</th>
                                                    <th className="p-4">Status</th>
                                                    <th className="p-4">Period End</th>
                                                    <th className="p-4">Auto Renew</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-border">
                                                {subscriptions.length === 0 ? (
                                                    <tr>
                                                        <td colSpan={5} className="p-4 text-center text-muted-foreground">
                                                            No subscription history found.
                                                        </td>
                                                    </tr>
                                                ) : (
                                                    subscriptions.map((sub) => (
                                                        <tr key={sub.id}>
                                                            <td className="p-4 font-semibold">{sub.plan.name}</td>
                                                            <td className="p-4 font-mono text-xs text-muted-foreground">{sub.stripe_id || 'Manual'}</td>
                                                            <td className="p-4">
                                                                <Badge variant="outline" className="uppercase text-[10px] font-bold">
                                                                    {sub.status}
                                                                </Badge>
                                                            </td>
                                                            <td className="p-4 text-muted-foreground">
                                                                {sub.ends_at ? new Date(sub.ends_at).toLocaleDateString() : 'N/A'}
                                                            </td>
                                                            <td className="p-4">
                                                                {sub.auto_renew ? 'Yes' : 'No'}
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
                    )}

                    {activeTab === 'activity' && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Login History */}
                            <Card className="border border-border">
                                <CardHeader>
                                    <CardTitle className="text-base font-bold">Authentication Logins</CardTitle>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <div className="max-h-[400px] overflow-y-auto divide-y divide-border">
                                        {loginHistory.length === 0 ? (
                                            <p className="p-4 text-sm text-center text-muted-foreground">No recent logins.</p>
                                        ) : (
                                            loginHistory.map((lh) => (
                                                <div key={lh.id} className="p-4 flex justify-between text-xs gap-4">
                                                    <div>
                                                        <p className="font-semibold text-foreground">IP: {lh.ip_address}</p>
                                                        <p className="text-[10px] text-muted-foreground font-mono truncate max-w-sm">
                                                            {lh.user_agent}
                                                        </p>
                                                    </div>
                                                    <span className="text-muted-foreground shrink-0">{new Date(lh.login_at).toLocaleString()}</span>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Audit Logs */}
                            <Card className="border border-border">
                                <CardHeader>
                                    <CardTitle className="text-base font-bold">Audit Action Logs</CardTitle>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <div className="max-h-[400px] overflow-y-auto divide-y divide-border">
                                        {activityLogs.length === 0 ? (
                                            <p className="p-4 text-sm text-center text-muted-foreground">No activities recorded.</p>
                                        ) : (
                                            activityLogs.map((log) => (
                                                <div key={log.id} className="p-4 text-xs space-y-1">
                                                    <div className="flex justify-between">
                                                        <span className="font-bold uppercase text-primary text-[10px]">{log.event}</span>
                                                        <span className="text-muted-foreground">{new Date(log.created_at).toLocaleString()}</span>
                                                    </div>
                                                    <p className="text-muted-foreground">{log.description}</p>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {activeTab === 'notes' && (
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {/* Notes List */}
                            <Card className="border border-border md:col-span-2">
                                <CardHeader>
                                    <CardTitle className="text-base font-bold">Admin Notes</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4 divide-y divide-border">
                                    {userNotes.length === 0 ? (
                                        <p className="text-sm text-muted-foreground py-4">No notes added yet.</p>
                                    ) : (
                                        userNotes.map((note) => (
                                            <div key={note.id} className="pt-4 first:pt-0 flex justify-between gap-4">
                                                <div>
                                                    <p className="text-sm text-foreground">{note.content}</p>
                                                    <p className="text-[10px] text-muted-foreground mt-1">
                                                        By <span className="font-semibold">{note.admin?.name || 'Admin'}</span> on {new Date(note.created_at).toLocaleString()}
                                                    </p>
                                                </div>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => handleDeleteNote(note.id)}
                                                    className="h-8 w-8 text-muted-foreground hover:text-rose-500 cursor-pointer"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        ))
                                    )}
                                </CardContent>
                            </Card>

                            {/* Add Note Form & Credits info */}
                            <div className="space-y-6 md:col-span-1">
                                <Card className="border border-border">
                                    <CardHeader>
                                        <CardTitle className="text-sm font-bold">Add Admin Note</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <form onSubmit={handleAddNote} className="space-y-3">
                                            <textarea
                                                className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring"
                                                placeholder="Write internal staff note here..."
                                                value={noteContent}
                                                onChange={(e) => setNoteContent(e.target.value)}
                                                required
                                            />
                                            <Button type="submit" disabled={addingNote} size="sm" className="w-full">
                                                {addingNote && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                                Save Note
                                            </Button>
                                        </form>
                                    </CardContent>
                                </Card>

                                <Card className="border border-border">
                                    <CardHeader>
                                        <CardTitle className="text-sm font-bold">Virtual Credits Ledger</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-3 text-xs">
                                        <div className="flex justify-between font-bold text-sm border-b border-border pb-2">
                                            <span>Current Balance</span>
                                            <span className="text-primary">
                                                {credits.reduce((acc, c) => acc + (c.amount || 0), 0)} credits
                                            </span>
                                        </div>
                                        <div className="space-y-2 max-h-[150px] overflow-y-auto">
                                            {credits.length === 0 ? (
                                                <p className="text-muted-foreground text-center py-2">No credits history.</p>
                                            ) : (
                                                credits.map((c) => (
                                                    <div key={c.id} className="flex justify-between py-1 border-b border-border last:border-0">
                                                        <span>{c.description || 'Adjustment'}</span>
                                                        <span className={c.amount >= 0 ? 'text-emerald-500 font-semibold' : 'text-rose-500 font-semibold'}>
                                                            {c.amount >= 0 ? `+${c.amount}` : c.amount}
                                                        </span>
                                                    </div>
                                                ))
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    )}

                    {activeTab === 'notifications' && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Notification logs */}
                            <Card className="border border-border">
                                <CardHeader>
                                    <CardTitle className="text-base font-bold">Notification Despatch History</CardTitle>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <div className="max-h-[400px] overflow-y-auto divide-y divide-border">
                                        {notificationLogs.length === 0 ? (
                                            <p className="p-4 text-sm text-center text-muted-foreground">No notifications sent.</p>
                                        ) : (
                                            notificationLogs.map((log) => (
                                                <div key={log.id} className="p-4 text-xs space-y-1">
                                                    <div className="flex justify-between">
                                                        <span className="font-semibold text-foreground capitalize">Template: {log.template_key}</span>
                                                        <span className="text-muted-foreground">{new Date(log.sent_at || log.created_at).toLocaleString()}</span>
                                                    </div>
                                                    <p className="text-muted-foreground">Channel: <Badge variant="outline" className="text-[9px]">{log.channel}</Badge></p>
                                                    <p className="text-muted-foreground">Status: <span className={log.status === 'sent' || log.status === 'success' ? 'text-emerald-500' : 'text-rose-500'}>{log.status}</span></p>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Registered FCM Devices */}
                            <Card className="border border-border">
                                <CardHeader>
                                    <CardTitle className="text-base font-bold">Registered FCM Devices</CardTitle>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <div className="max-h-[400px] overflow-y-auto divide-y divide-border">
                                        {fcmTokens.length === 0 ? (
                                            <p className="p-4 text-sm text-center text-muted-foreground">No registered push tokens.</p>
                                        ) : (
                                            fcmTokens.map((token) => (
                                                <div key={token.id} className="p-4 text-xs space-y-1">
                                                    <div className="flex justify-between">
                                                        <span className="font-semibold text-foreground uppercase">OS: {token.device_type}</span>
                                                        <span>
                                                            {token.is_active ? (
                                                                <Badge className="bg-emerald-500/10 text-emerald-500">Active</Badge>
                                                            ) : (
                                                                <Badge variant="outline">Inactive</Badge>
                                                            )}
                                                        </span>
                                                    </div>
                                                    <p className="text-muted-foreground font-mono text-[10px] truncate max-w-sm">
                                                        Token: {token.token}
                                                    </p>
                                                    <p className="text-[10px] text-muted-foreground">Updated: {new Date(token.updated_at).toLocaleDateString()}</p>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {activeTab === 'roles' && (
                        <div className="max-w-2xl">
                            <Card className="border border-border">
                                <CardHeader>
                                    <CardTitle className="text-base font-bold flex items-center gap-2">
                                        <Shield className="h-5 w-5 text-primary" />
                                        Role Assignment
                                    </CardTitle>
                                    <CardDescription>
                                        Select the roles to assign to this user. Changes take effect immediately upon saving.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {allRoles.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">No roles found. Run the permission seeder first.</p>
                                    ) : (
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            {allRoles.map((role) => {
                                                const checked = selectedRoles.includes(role.id);
                                                return (
                                                    <label
                                                        key={role.id}
                                                        className={`flex items-center gap-3 rounded-xl border p-4 cursor-pointer transition-all select-none ${
                                                            checked
                                                                ? 'border-primary bg-primary/5 text-foreground'
                                                                : 'border-border bg-muted/20 text-muted-foreground hover:border-primary/50'
                                                        }`}
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            className="h-4 w-4 rounded border-gray-300 text-primary accent-primary"
                                                            checked={checked}
                                                            onChange={() => toggleRole(role.id)}
                                                        />
                                                        <div>
                                                            <p className="font-semibold text-sm text-foreground">{role.name}</p>
                                                        </div>
                                                        {checked && (
                                                            <Badge className="ml-auto text-[10px] bg-primary/10 text-primary border-primary/20">Active</Badge>
                                                        )}
                                                    </label>
                                                );
                                            })}
                                        </div>
                                    )}
                                </CardContent>
                                <CardFooter className="border-t border-border pt-4 flex justify-between items-center">
                                    <p className="text-xs text-muted-foreground">
                                        {selectedRoles.length} role{selectedRoles.length !== 1 ? 's' : ''} selected
                                    </p>
                                    <Button
                                        onClick={handleSaveRoles}
                                        disabled={savingRoles}
                                        className="bg-primary text-primary-foreground hover:bg-primary/90"
                                    >
                                        {savingRoles && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                        Save Role Changes
                                    </Button>
                                </CardFooter>
                            </Card>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

UserShow.layout = {
    breadcrumbs: [
        { title: 'Users Manager', href: '/admin/users' },
        { title: 'User Details', href: '#' },
    ],
};
