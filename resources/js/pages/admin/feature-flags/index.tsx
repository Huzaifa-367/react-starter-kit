import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ToggleLeft, Plus, Edit2, Trash2, Loader2, Sparkles, Check, X } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { toast } from 'sonner';

interface FeatureFlagItem {
    id: number;
    key: string;
    description: string | null;
    enabled_globally: boolean;
    enabled_for_plans: string[];
    enabled_for_roles: string[];
    enabled_for_users: number[];
    created_at: string;
}

interface PlanOption {
    id: number;
    name: string;
    slug: string;
}

interface RoleOption {
    id: number;
    name: string;
}

interface Props {
    flags: FeatureFlagItem[];
    plans: PlanOption[];
    roles: RoleOption[];
}

export default function FeatureFlagsIndex({ flags, plans, roles }: Props) {
    const [actionLoading, setActionLoading] = useState<string | null>(null);
    const [editorOpen, setEditorOpen] = useState(false);
    const [editingFlag, setEditingFlag] = useState<FeatureFlagItem | null>(null);

    // Form fields
    const [key, setKey] = useState('');
    const [description, setDescription] = useState('');
    const [enabledGlobally, setEnabledGlobally] = useState(false);
    const [selectedPlans, setSelectedPlans] = useState<string[]>([]);
    const [selectedRoles, setSelectedRoles] = useState<string[]>([]);
    const [userIdsText, setUserIdsText] = useState('');

    const openCreateDialog = () => {
        setEditingFlag(null);
        setKey('');
        setDescription('');
        setEnabledGlobally(false);
        setSelectedPlans([]);
        setSelectedRoles([]);
        setUserIdsText('');
        setEditorOpen(true);
    };

    const openEditDialog = (flag: FeatureFlagItem) => {
        setEditingFlag(flag);
        setKey(flag.key);
        setDescription(flag.description || '');
        setEnabledGlobally(flag.enabled_globally);
        setSelectedPlans(flag.enabled_for_plans || []);
        setSelectedRoles(flag.enabled_for_roles || []);
        setUserIdsText((flag.enabled_for_users || []).join(', '));
        setEditorOpen(true);
    };

    const handlePlanToggle = (slug: string) => {
        setSelectedPlans(prev =>
            prev.includes(slug) ? prev.filter(s => s !== slug) : [...prev, slug]
        );
    };

    const handleRoleToggle = (name: string) => {
        setSelectedRoles(prev =>
            prev.includes(name) ? prev.filter(r => r !== name) : [...prev, name]
        );
    };

    const handleFormSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setActionLoading('submit');

        const parsedUserIds = userIdsText
            .split(',')
            .map(id => parseInt(id.trim()))
            .filter(id => !isNaN(id));

        const payload = {
            key,
            description,
            enabled_globally: enabledGlobally,
            enabled_for_plans: selectedPlans,
            enabled_for_roles: selectedRoles,
            enabled_for_users: parsedUserIds,
        };

        if (editingFlag) {
            router.put(`/admin/feature-flags/${editingFlag.id}`, payload, {
                onSuccess: () => {
                    toast.success('Feature flag updated successfully.');
                    setEditorOpen(false);
                },
                onError: (err) => {
                    toast.error(Object.values(err)[0] || 'Verification failed.');
                },
                onFinish: () => setActionLoading(null),
            });
        } else {
            router.post('/admin/feature-flags', payload, {
                onSuccess: () => {
                    toast.success('Feature flag created successfully.');
                    setEditorOpen(false);
                },
                onError: (err) => {
                    toast.error(Object.values(err)[0] || 'Verification failed.');
                },
                onFinish: () => setActionLoading(null),
            });
        }
    };

    const handleDelete = (flag: FeatureFlagItem) => {
        if (!confirm(`Are you sure you want to permanently delete feature flag "${flag.key}"?`)) return;
        setActionLoading(`delete-${flag.id}`);
        router.delete(`/admin/feature-flags/${flag.id}`, {
            onSuccess: () => toast.success('Feature flag deleted successfully.'),
            onFinish: () => setActionLoading(null),
        });
    };

    return (
        <>
            <Head title="Feature Flags Configuration" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            Feature Flags
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Control application feature availability for target subscription plans, roles, or users.
                        </p>
                    </div>
                    <div>
                        <Button onClick={openCreateDialog} className="cursor-pointer">
                            <Plus className="mr-2 h-4 w-4" />
                            Create Flag
                        </Button>
                    </div>
                </div>

                {/* Table */}
                <Card className="border border-border">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="bg-muted/40 text-xs font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                                        <th className="p-4">Key & Description</th>
                                        <th className="p-4">Global Status</th>
                                        <th className="p-4">Plans Scope</th>
                                        <th className="p-4">Roles Scope</th>
                                        <th className="p-4">Users Target</th>
                                        <th className="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border text-sm">
                                    {flags.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="p-8 text-center text-muted-foreground font-mono">
                                                No feature flags configured.
                                            </td>
                                        </tr>
                                    ) : (
                                        flags.map((flag) => (
                                            <tr key={flag.id} className="hover:bg-muted/10 transition-colors">
                                                <td className="p-4">
                                                    <div className="flex flex-col">
                                                        <span className="font-semibold text-foreground font-mono text-sm">
                                                            {flag.key}
                                                        </span>
                                                        <span className="text-xs text-muted-foreground mt-0.5 max-w-xs">
                                                            {flag.description || 'No description provided.'}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="p-4">
                                                    {flag.enabled_globally ? (
                                                        <Badge className="bg-emerald-500/10 text-emerald-500 border-none font-bold">
                                                            <Check className="h-3 w-3 mr-1" /> ACTIVE GLOBALLY
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="secondary" className="text-muted-foreground font-bold">
                                                            <X className="h-3 w-3 mr-1" /> RULES GATED
                                                        </Badge>
                                                    )}
                                                </td>
                                                <td className="p-4 max-w-xs">
                                                    <div className="flex flex-wrap gap-1">
                                                        {flag.enabled_for_plans?.length > 0 ? (
                                                            flag.enabled_for_plans.map(p => (
                                                                <Badge key={p} variant="outline" className="text-[10px] bg-primary/5 font-semibold">
                                                                    {p}
                                                                </Badge>
                                                            ))
                                                        ) : (
                                                            <span className="text-muted-foreground text-xs font-mono">—</span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="p-4">
                                                    <div className="flex flex-wrap gap-1">
                                                        {flag.enabled_for_roles?.length > 0 ? (
                                                            flag.enabled_for_roles.map(r => (
                                                                <Badge key={r} variant="outline" className="text-[10px] font-semibold">
                                                                    {r}
                                                                </Badge>
                                                            ))
                                                        ) : (
                                                            <span className="text-muted-foreground text-xs font-mono">—</span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="p-4 text-xs font-mono text-muted-foreground">
                                                    {flag.enabled_for_users?.length > 0 ? (
                                                        <span>{flag.enabled_for_users.length} Users ({flag.enabled_for_users.join(', ')})</span>
                                                    ) : (
                                                        <span>—</span>
                                                    )}
                                                </td>
                                                <td className="p-4 text-right space-x-1.5 whitespace-nowrap">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => openEditDialog(flag)}
                                                        className="h-8 w-8 hover:bg-muted cursor-pointer"
                                                    >
                                                        <Edit2 className="h-4 w-4 text-amber-500" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => handleDelete(flag)}
                                                        disabled={actionLoading === `delete-${flag.id}`}
                                                        className="h-8 w-8 hover:bg-rose-500/10 hover:text-rose-500 cursor-pointer"
                                                    >
                                                        {actionLoading === `delete-${flag.id}` ? (
                                                            <Loader2 className="h-4 w-4 animate-spin" />
                                                        ) : (
                                                            <Trash2 className="h-4 w-4" />
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

                {/* Editor Modal */}
                <Dialog open={editorOpen} onOpenChange={setEditorOpen}>
                    <DialogContent className="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>{editingFlag ? 'Edit Feature Flag' : 'New Feature Flag'}</DialogTitle>
                            <DialogDescription>
                                Set scoping rules. If active globally, target rules are bypassed.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleFormSubmit} className="space-y-4 py-2">
                            <div className="grid gap-2">
                                <Label htmlFor="key">Flag Key (Slug)</Label>
                                <Input
                                    id="key"
                                    placeholder="e.g. beta_billing_portal"
                                    value={key}
                                    onChange={(e) => setKey(e.target.value)}
                                    required
                                    disabled={!!editingFlag}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Input
                                    id="description"
                                    placeholder="Controls access to billing operations..."
                                    value={description}
                                    onChange={(e) => setDescription(e.target.value)}
                                />
                            </div>

                            <div className="flex items-center gap-2 py-1 cursor-pointer select-none">
                                <input
                                    id="enabled_globally"
                                    type="checkbox"
                                    checked={enabledGlobally}
                                    onChange={(e) => setEnabledGlobally(e.target.checked)}
                                    className="rounded border-input text-primary focus:ring-primary h-4 w-4"
                                />
                                <Label htmlFor="enabled_globally" className="cursor-pointer">Enable Globally (Overrides target rules)</Label>
                            </div>

                            {!enabledGlobally && (
                                <div className="space-y-4 pt-2 border-t border-border">
                                    <div className="grid gap-2">
                                        <Label>Restrict to Plans</Label>
                                        <div className="grid grid-cols-2 gap-2 mt-1">
                                            {plans.map((p) => (
                                                <label key={p.id} className="flex items-center gap-2 text-xs cursor-pointer select-none">
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedPlans.includes(p.slug)}
                                                        onChange={() => handlePlanToggle(p.slug)}
                                                        className="rounded border-input text-primary focus:ring-primary h-3.5 w-3.5"
                                                    />
                                                    <span>{p.name} ({p.slug})</span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label>Restrict to Roles</Label>
                                        <div className="grid grid-cols-2 gap-2 mt-1">
                                            {roles.map((r) => (
                                                <label key={r.id} className="flex items-center gap-2 text-xs cursor-pointer select-none">
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedRoles.includes(r.name)}
                                                        onChange={() => handleRoleToggle(r.name)}
                                                        className="rounded border-input text-primary focus:ring-primary h-3.5 w-3.5"
                                                    />
                                                    <span>{r.name}</span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="user_ids">Restrict to Specific User IDs</Label>
                                        <Input
                                            id="user_ids"
                                            placeholder="e.g. 1, 42, 105 (comma separated)"
                                            value={userIdsText}
                                            onChange={(e) => setUserIdsText(e.target.value)}
                                        />
                                        <span className="text-[10px] text-muted-foreground">Type user IDs separated by commas.</span>
                                    </div>
                                </div>
                            )}

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setEditorOpen(false)}>Cancel</Button>
                                <Button type="submit" disabled={actionLoading === 'submit'} className="cursor-pointer">
                                    {actionLoading === 'submit' && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    Save Config
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

FeatureFlagsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Feature Flags',
            href: '/admin/feature-flags',
        },
    ],
};
