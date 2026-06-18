import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Lock, Plus, Edit2, Trash2, Loader2, Check, X, ShieldAlert, ShieldCheck } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { toast } from 'sonner';

interface IpRuleItem {
    id: number;
    ip: string;
    type: 'allow' | 'block';
    reason: string | null;
    is_active: boolean;
    created_at: string;
}

interface Props {
    rules: {
        data: IpRuleItem[];
        links: any[];
        total: number;
        current_page: number;
        last_page: number;
    };
}

export default function IpRulesIndex({ rules }: Props) {
    const [actionLoading, setActionLoading] = useState<string | null>(null);
    const [editorOpen, setEditorOpen] = useState(false);
    const [editingRule, setEditingRule] = useState<IpRuleItem | null>(null);

    // Form fields
    const [ip, setIp] = useState('');
    const [type, setType] = useState<'allow' | 'block'>('block');
    const [reason, setReason] = useState('');
    const [isActive, setIsActive] = useState(true);

    const openCreateDialog = () => {
        setEditingRule(null);
        setIp('');
        setType('block');
        setReason('');
        setIsActive(true);
        setEditorOpen(true);
    };

    const openEditDialog = (rule: IpRuleItem) => {
        setEditingRule(rule);
        setIp(rule.ip);
        setType(rule.type);
        setReason(rule.reason || '');
        setIsActive(rule.is_active);
        setEditorOpen(true);
    };

    const handleFormSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setActionLoading('submit');

        const payload = {
            ip,
            type,
            reason,
            is_active: isActive,
        };

        if (editingRule) {
            router.put(`/admin/ip-rules/${editingRule.id}`, payload, {
                onSuccess: () => {
                    toast.success('IP rule updated successfully.');
                    setEditorOpen(false);
                },
                onError: (err) => {
                    toast.error(Object.values(err)[0] || 'Verification failed.');
                },
                onFinish: () => setActionLoading(null),
            });
        } else {
            router.post('/admin/ip-rules', payload, {
                onSuccess: () => {
                    toast.success('IP rule created successfully.');
                    setEditorOpen(false);
                },
                onError: (err) => {
                    toast.error(Object.values(err)[0] || 'Verification failed.');
                },
                onFinish: () => setActionLoading(null),
            });
        }
    };

    const handleDelete = (rule: IpRuleItem) => {
        if (!confirm(`Are you sure you want to permanently delete IP rule for "${rule.ip}"?`)) return;
        setActionLoading(`delete-${rule.id}`);
        router.delete(`/admin/ip-rules/${rule.id}`, {
            onSuccess: () => toast.success('IP rule deleted successfully.'),
            onFinish: () => setActionLoading(null),
        });
    };

    return (
        <>
            <Head title="Firewall IP Rules" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            IP Firewall Allow/Block List
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Manage access rules for specific client IP addresses or entire CIDR network blocks.
                        </p>
                    </div>
                    <div>
                        <Button onClick={openCreateDialog} className="cursor-pointer">
                            <Plus className="mr-2 h-4 w-4" />
                            Add IP Rule
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
                                        <th className="p-4">IP / CIDR Range</th>
                                        <th className="p-4">Rule Type</th>
                                        <th className="p-4">Reason / Notes</th>
                                        <th className="p-4">Firewall Status</th>
                                        <th className="p-4">Created At</th>
                                        <th className="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border text-sm">
                                    {rules.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="p-8 text-center text-muted-foreground font-mono">
                                                No IP rules configured. Firewall is wide open.
                                            </td>
                                        </tr>
                                    ) : (
                                        rules.data.map((rule) => (
                                            <tr key={rule.id} className="hover:bg-muted/10 transition-colors">
                                                <td className="p-4 font-mono font-semibold text-foreground text-sm">
                                                    {rule.ip}
                                                </td>
                                                <td className="p-4">
                                                    {rule.type === 'allow' ? (
                                                        <Badge className="bg-emerald-500/10 text-emerald-500 border-none font-bold flex items-center w-max gap-1">
                                                            <ShieldCheck className="h-3 w-3" /> ALLOWED
                                                        </Badge>
                                                    ) : (
                                                        <Badge className="bg-rose-500/10 text-rose-500 border-none font-bold flex items-center w-max gap-1">
                                                            <ShieldAlert className="h-3 w-3" /> BLOCKED
                                                        </Badge>
                                                    )}
                                                </td>
                                                <td className="p-4 text-muted-foreground max-w-xs truncate">
                                                    {rule.reason || '—'}
                                                </td>
                                                <td className="p-4">
                                                    {rule.is_active ? (
                                                        <Badge variant="outline" className="text-[10px] bg-emerald-500/5 text-emerald-600 dark:text-emerald-400 font-bold">
                                                            ACTIVE
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="outline" className="text-[10px] bg-muted text-muted-foreground font-bold">
                                                            DISABLED
                                                        </Badge>
                                                    )}
                                                </td>
                                                <td className="p-4 text-muted-foreground">
                                                    {new Date(rule.created_at).toLocaleDateString()}
                                                </td>
                                                <td className="p-4 text-right space-x-1.5 whitespace-nowrap">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => openEditDialog(rule)}
                                                        className="h-8 w-8 hover:bg-muted cursor-pointer"
                                                    >
                                                        <Edit2 className="h-4 w-4 text-amber-500" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => handleDelete(rule)}
                                                        disabled={actionLoading === `delete-${rule.id}`}
                                                        className="h-8 w-8 hover:bg-rose-500/10 hover:text-rose-500 cursor-pointer"
                                                    >
                                                        {actionLoading === `delete-${rule.id}` ? (
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

                        {/* Pagination */}
                        {rules.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-border px-4 py-3 sm:px-6">
                                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                    <p className="text-sm text-muted-foreground">
                                        Showing page <span className="font-semibold text-foreground">{rules.current_page}</span> of{' '}
                                        <span className="font-semibold text-foreground">{rules.last_page}</span> ({rules.total} total rules)
                                    </p>
                                    <nav className="isolate inline-flex -space-x-px rounded-md shadow-xs" aria-label="Pagination">
                                        {rules.links.map((link: any, idx: number) => (
                                            <Link
                                                key={idx}
                                                href={link.url || '#'}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                className={`relative inline-flex items-center px-4 py-2 text-sm font-semibold border border-border focus:z-20 ${
                                                    link.active
                                                        ? 'z-10 bg-primary text-primary-foreground focus-visible:outline-2'
                                                        : 'text-foreground hover:bg-accent bg-background'
                                                } ${!link.url ? 'pointer-events-none opacity-50' : ''} ${
                                                    idx === 0 ? 'rounded-l-md' : idx === rules.links.length - 1 ? 'rounded-r-md' : ''
                                                }`}
                                            />
                                        ))}
                                    </nav>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Editor Modal */}
                <Dialog open={editorOpen} onOpenChange={setEditorOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>{editingRule ? 'Edit IP Rule' : 'Add IP Firewall Rule'}</DialogTitle>
                            <DialogDescription>
                                Input a single IPv4/IPv6 address or a CIDR subnet block (e.g. 192.168.1.0/24).
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleFormSubmit} className="space-y-4 py-2">
                            <div className="grid gap-2">
                                <Label htmlFor="ip">IP Address or CIDR Subnet</Label>
                                <Input
                                    id="ip"
                                    placeholder="e.g. 185.23.102.4 or 192.168.1.0/24"
                                    value={ip}
                                    onChange={(e) => setIp(e.target.value)}
                                    required
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="type">Rule Type Action</Label>
                                <select
                                    id="type"
                                    value={type}
                                    onChange={(e) => setType(e.target.value as any)}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                >
                                    <option value="block">BLOCK ACCESS (Deny)</option>
                                    <option value="allow">ALLOW ACCESS (Bypass blocklists)</option>
                                </select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="reason">Reason / Internal Notes</Label>
                                <Input
                                    id="reason"
                                    placeholder="e.g. DDOS attacker bot, VPN gateway, dev team office..."
                                    value={reason}
                                    onChange={(e) => setReason(e.target.value)}
                                />
                            </div>

                            <div className="flex items-center gap-2 py-1 cursor-pointer select-none">
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    checked={isActive}
                                    onChange={(e) => setIsActive(e.target.checked)}
                                    className="rounded border-input text-primary focus:ring-primary h-4 w-4"
                                />
                                <Label htmlFor="is_active" className="cursor-pointer">Enable rule immediately</Label>
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setEditorOpen(false)}>Cancel</Button>
                                <Button type="submit" disabled={actionLoading === 'submit'} className="cursor-pointer">
                                    {actionLoading === 'submit' && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    Save Rule
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

IpRulesIndex.layout = {
    breadcrumbs: [
        {
            title: 'IP Rules',
            href: '/admin/ip-rules',
        },
    ],
};
