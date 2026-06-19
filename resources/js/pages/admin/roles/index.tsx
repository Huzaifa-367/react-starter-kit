import React, { useState, useEffect } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import Heading from '@/components/heading';
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
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Shield, ShieldAlert, Plus, Edit2, Trash2, Check, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface PermissionItem {
    id: number;
    name: string;
}

interface RoleItem {
    id: number;
    name: string;
    permissions: PermissionItem[];
    permissions_count: number;
    users_count: number;
}

const getPermissionGroup = (name: string): string => {
    if (name.startsWith('admin.users.')) return 'User Management';
    if (name === 'admin.users') return 'User Management';
    if (name.startsWith('admin.roles.')) return 'Role Management';
    if (name === 'admin.roles') return 'Role Management';
    if (name.startsWith('admin.plans.')) return 'Plan Management';
    if (name === 'admin.plans') return 'Plan Management';
    if (name.startsWith('admin.coupons.')) return 'Coupon Management';
    if (name === 'admin.coupons') return 'Coupon Management';
    if (name.startsWith('admin.segments.')) return 'Segment Management';
    if (name === 'admin.segments') return 'Segment Management';
    if (name.startsWith('admin.broadcasts.')) return 'Broadcast Management';
    if (name === 'admin.broadcasts') return 'Broadcast Management';
    if (name.startsWith('admin.email-templates.')) return 'Email Templates';
    if (name === 'admin.email-templates') return 'Email Templates';
    if (name.startsWith('admin.feature-flags.')) return 'Feature Flags';
    if (name === 'admin.feature-flags') return 'Feature Flags';
    if (name.startsWith('admin.ip-rules.')) return 'IP Rules';
    if (name === 'admin.ip-rules') return 'IP Rules';
    if (name.startsWith('admin.diagnostics.')) return 'System Diagnostics';
    if (name.startsWith('admin.failed-jobs.')) return 'System Management';
    if (name.startsWith('admin.rate-limits.')) return 'System Management';
    if (name.startsWith('admin.webhook-logs.')) return 'System Management';
    if (name.startsWith('admin.logs.')) return 'System Management';
    if (name.startsWith('admin.activity.')) return 'System Management';
    if (name.startsWith('admin.settings') || name.startsWith('admin.maintenance') || name === 'admin.system-health' || name === 'admin.cache.flush' || name === 'admin.dashboard' || name === 'admin.analytics' || name === 'admin.analytics.export' || name === 'admin.impersonation.stop') return 'System & Analytics';
    if (name.startsWith('billing.')) return 'Billing Operations';
    if (name.startsWith('profile.') || name.startsWith('security.') || name === 'appearance.edit' || name === 'dashboard' || name === 'pricing.subscribed' || name === 'user-password.update') return 'User Profile & Common';
    
    return 'General / Unclassified';
};

interface Props {
    roles: RoleItem[];
    permissions: PermissionItem[];
}

export default function RolesIndex({ roles, permissions }: Props) {
    const [selectedRole, setSelectedRole] = useState<RoleItem | null>(roles[0] || null);
    const [selectedPermissions, setSelectedPermissions] = useState<string[]>([]);

    const groupedPermissions: Record<string, PermissionItem[]> = {};
    permissions.forEach((perm) => {
        const group = getPermissionGroup(perm.name);
        if (!groupedPermissions[group]) {
            groupedPermissions[group] = [];
        }
        groupedPermissions[group].push(perm);
    });

    const handleSelectAllGroup = (groupPerms: PermissionItem[]) => {
        setSelectedPermissions((prev) => {
            const newPerms = [...prev];
            groupPerms.forEach((p) => {
                if (!newPerms.includes(p.name)) {
                    newPerms.push(p.name);
                }
            });
            return newPerms;
        });
    };

    const handleDeselectAllGroup = (groupPerms: PermissionItem[]) => {
        const namesToRemove = groupPerms.map((p) => p.name);
        setSelectedPermissions((prev) => prev.filter((name) => !namesToRemove.includes(name)));
    };

    // Modal states
    const [createOpen, setCreateOpen] = useState(false);
    const [editOpen, setEditOpen] = useState(false);
    const [actionLoading, setActionLoading] = useState<string | null>(null);

    const createForm = useForm({
        name: '',
    });

    const editForm = useForm({
        name: '',
    });

    // Update permission matrix when active role changes
    useEffect(() => {
        if (selectedRole) {
            setSelectedPermissions(selectedRole.permissions.map((p) => p.name));
        } else {
            setSelectedPermissions([]);
        }
    }, [selectedRole]);

    const handleCreateRole = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/admin/roles', {
            onSuccess: () => {
                toast.success('Role created successfully.');
                createForm.reset();
                setCreateOpen(false);
            },
            onError: (err) => {
                toast.error(err.name || 'Failed to create role.');
            },
        });
    };

    const handleEditRole = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedRole) return;
        editForm.put(`/admin/roles/${selectedRole.id}`, {
            onSuccess: () => {
                toast.success('Role updated.');
                setEditOpen(false);
            },
            onError: (err) => {
                toast.error(err.name || 'Failed to update role.');
            },
        });
    };

    const handleDeleteRole = (role: RoleItem) => {
        if (role.users_count > 0) {
            toast.error('You cannot delete a role with active users.');
            return;
        }
        if (!confirm(`Are you sure you want to delete the role "${role.name}"?`)) return;

        setActionLoading(`delete-${role.id}`);
        router.delete(`/admin/roles/${role.id}`, {
            onSuccess: () => {
                toast.success('Role deleted.');
                if (selectedRole?.id === role.id) {
                    setSelectedRole(roles.find((r) => r.id !== role.id) || null);
                }
            },
            onFinish: () => setActionLoading(null),
        });
    };

    const handlePermissionToggle = (permissionName: string) => {
        setSelectedPermissions((prev) =>
            prev.includes(permissionName)
                ? prev.filter((p) => p !== permissionName)
                : [...prev, permissionName]
        );
    };

    const handleSyncPermissions = () => {
        if (!selectedRole) return;
        setActionLoading('sync');
        router.post(`/admin/roles/${selectedRole.id}/sync-permissions`, {
            permissions: selectedPermissions,
        }, {
            onSuccess: () => {
                toast.success('Role permissions updated successfully.');
                // Refresh local state of selected role references
                const updated = roles.find((r) => r.id === selectedRole.id);
                if (updated) setSelectedRole(updated);
            },
            onError: (err) => {
                toast.error('Failed to update permissions.');
            },
            onFinish: () => setActionLoading(null),
        });
    };

    return (
        <>
            <Head title="Roles & Permissions" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">Roles & Permissions</h1>
                        <p className="text-muted-foreground text-sm">
                            Configure user access control groups and permission assignments.
                        </p>
                    </div>
                    <Button onClick={() => setCreateOpen(true)} className="cursor-pointer">
                        <Plus className="mr-2 h-4 w-4" />
                        Create Role
                    </Button>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                    {/* Left side: Roles List */}
                    <Card className="border border-border lg:col-span-1">
                        <CardHeader>
                            <CardTitle className="text-base font-bold">Role Groups</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {roles.map((role) => (
                                <div
                                    key={role.id}
                                    onClick={() => setSelectedRole(role)}
                                    className={`flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all ${
                                        selectedRole?.id === role.id
                                            ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                            : 'border-border hover:bg-muted/10'
                                    }`}
                                >
                                    <div className="space-y-1">
                                        <p className="font-semibold text-sm text-foreground flex items-center gap-2">
                                            <Shield className="h-4 w-4 text-primary" />
                                            {role.name}
                                        </p>
                                        <div className="flex items-center gap-2 text-[10px] text-muted-foreground">
                                            <span>{role.users_count} users</span>
                                            <span>•</span>
                                            <span>{role.permissions_count} permissions</span>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-1.5" onClick={(e) => e.stopPropagation()}>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => {
                                                setSelectedRole(role);
                                                editForm.setData('name', role.name);
                                                setEditOpen(true);
                                            }}
                                            className="h-8 w-8 text-muted-foreground hover:text-foreground cursor-pointer"
                                        >
                                            <Edit2 className="h-3.5 w-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            disabled={role.users_count > 0 || actionLoading === `delete-${role.id}`}
                                            onClick={() => handleDeleteRole(role)}
                                            className="h-8 w-8 text-muted-foreground hover:text-rose-500 cursor-pointer"
                                        >
                                            {actionLoading === `delete-${role.id}` ? (
                                                <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                            ) : (
                                                <Trash2 className="h-3.5 w-3.5" />
                                            )}
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    {/* Right side: Permission Matrix */}
                    <Card className="border border-border lg:col-span-2">
                        {selectedRole ? (
                            <>
                                <CardHeader className="flex flex-row items-center justify-between pb-4 border-b border-border">
                                    <div className="space-y-1">
                                        <CardTitle className="text-base font-bold flex items-center gap-2">
                                            Permissions for "{selectedRole.name}"
                                        </CardTitle>
                                        <CardDescription>
                                            Select the features and actions authorized for this role.
                                        </CardDescription>
                                    </div>
                                    <Button
                                        onClick={handleSyncPermissions}
                                        disabled={actionLoading === 'sync'}
                                        className="cursor-pointer"
                                    >
                                        {actionLoading === 'sync' && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                        Save Matrix
                                    </Button>
                                </CardHeader>
                                <CardContent className="pt-6 space-y-8 max-h-[600px] overflow-y-auto">
                                    {Object.entries(groupedPermissions).map(([groupName, groupPerms]) => (
                                        <div key={groupName} className="space-y-3">
                                            <h3 className="text-sm font-bold text-foreground border-b border-border pb-1.5 flex items-center justify-between">
                                                <span>{groupName}</span>
                                                <div className="flex gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleSelectAllGroup(groupPerms)}
                                                        className="text-[10px] text-primary hover:underline cursor-pointer"
                                                    >
                                                        Select All
                                                    </button>
                                                    <span className="text-[10px] text-muted-foreground">|</span>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDeselectAllGroup(groupPerms)}
                                                        className="text-[10px] text-muted-foreground hover:text-rose-500 hover:underline cursor-pointer"
                                                    >
                                                        Deselect All
                                                    </button>
                                                </div>
                                            </h3>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                {groupPerms.map((perm) => {
                                                    const checked = selectedPermissions.includes(perm.name);
                                                    return (
                                                        <div
                                                            key={perm.id}
                                                            onClick={() => handlePermissionToggle(perm.name)}
                                                            className={`flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all ${
                                                                checked
                                                                    ? 'border-emerald-200 bg-emerald-500/5 dark:border-emerald-900/50'
                                                                    : 'border-border hover:bg-muted/10'
                                                            }`}
                                                        >
                                                            <div className={`h-5 w-5 rounded-md border flex items-center justify-center transition-all ${
                                                                checked
                                                                    ? 'bg-emerald-500 border-emerald-500 text-white'
                                                                    : 'border-border bg-background'
                                                            }`}>
                                                                {checked && <Check className="h-3.5 w-3.5 stroke-[3]" />}
                                                            </div>
                                                            <span className="text-sm font-medium text-foreground">{perm.name}</span>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ))}
                                </CardContent>
                            </>
                        ) : (
                            <div className="flex flex-col items-center justify-center py-16 text-center">
                                <ShieldAlert className="h-10 w-10 text-muted-foreground mb-3" />
                                <p className="text-sm text-muted-foreground">Select a role group to manage permissions.</p>
                            </div>
                        )}
                    </Card>
                </div>

                {/* Create Role Modal */}
                <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Create New Role</DialogTitle>
                            <DialogDescription>
                                Assign a name for the new security group.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleCreateRole} className="space-y-4 py-2">
                            <div className="grid gap-2">
                                <Label htmlFor="create-name">Role Name</Label>
                                <Input
                                    id="create-name"
                                    placeholder="e.g. Moderator, Manager"
                                    value={createForm.data.name}
                                    onChange={(e) => createForm.setData('name', e.target.value)}
                                    required
                                />
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setCreateOpen(false)}>Cancel</Button>
                                <Button type="submit" disabled={createForm.processing}>
                                    {createForm.processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    Create Role
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Edit Role Modal */}
                <Dialog open={editOpen} onOpenChange={setEditOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Rename Role Group</DialogTitle>
                            <DialogDescription>
                                Edit the name of the role group.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleEditRole} className="space-y-4 py-2">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-name">Role Name</Label>
                                <Input
                                    id="edit-name"
                                    value={editForm.data.name}
                                    onChange={(e) => editForm.setData('name', e.target.value)}
                                    required
                                />
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setEditOpen(false)}>Cancel</Button>
                                <Button type="submit" disabled={editForm.processing}>
                                    {editForm.processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    Save Changes
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Roles & Permissions',
            href: '/admin/roles',
        },
    ],
};
