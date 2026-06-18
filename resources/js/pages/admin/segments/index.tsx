import React, { useState } from 'react';
import { Head, useForm, Link, router } from '@inertiajs/react';
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
import { Filter, Plus, Trash2, Download, Bell, Play, Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

interface SegmentItem {
    id: number;
    name: string;
    description: string | null;
    filters: Record<string, any>;
    member_count: number;
    created_at: string;
}

interface Props {
    segments: SegmentItem[];
}

export default function SegmentsIndex({ segments }: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [previewCount, setPreviewCount] = useState<number | null>(null);
    const [previewing, setPreviewing] = useState(false);

    const form = useForm({
        name: '',
        description: '',
        filters: {
            role: '',
            plan: '',
            is_suspended: '',
        },
    });

    const handleFilterChange = (key: string, val: string) => {
        form.setData('filters', {
            ...form.data.filters,
            [key]: val,
        });
        setPreviewCount(null);
    };

    const handlePreview = async () => {
        setPreviewing(true);
        try {
            const response = await axios.post('/admin/segments/preview', {
                filters: form.data.filters,
            });
            setPreviewCount(response.data.count);
        } catch (error) {
            toast.error('Failed to preview matches.');
        } finally {
            setPreviewing(false);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/admin/segments', {
            onSuccess: () => {
                toast.success('Segment created and users synchronized.');
                form.reset();
                setCreateOpen(false);
                setPreviewCount(null);
            },
        });
    };

    const handleDelete = (id: number) => {
        if (!confirm('Are you sure you want to delete this segment?')) return;
        router.delete(`/admin/segments/${id}`, {
            onSuccess: () => toast.success('Segment deleted successfully.'),
        });
    };

    return (
        <>
            <Head title="User Segments" />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">User Segments</h1>
                        <p className="text-muted-foreground text-sm">
                            Create dynamic filter subsets of customers for campaigns and exports.
                        </p>
                    </div>
                    <Button onClick={() => setCreateOpen(true)} className="cursor-pointer">
                        <Plus className="mr-2 h-4 w-4" />
                        New Segment
                    </Button>
                </div>

                {/* Segments list card */}
                <Card className="border border-border">
                    <CardHeader>
                        <CardTitle className="text-base font-bold">Segmentation Rules</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr className="bg-muted/40 font-bold text-muted-foreground border-b border-border">
                                        <th className="p-4">Segment Name</th>
                                        <th className="p-4">Rules Summary</th>
                                        <th className="p-4">Match Count</th>
                                        <th className="p-4">Created</th>
                                        <th className="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {segments.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="p-8 text-center text-muted-foreground">
                                                No segments created yet.
                                            </td>
                                        </tr>
                                    ) : (
                                        segments.map((seg) => (
                                            <tr key={seg.id} className="hover:bg-muted/10 transition-colors">
                                                <td className="p-4">
                                                    <div className="flex flex-col">
                                                        <span className="font-semibold text-foreground">{seg.name}</span>
                                                        <span className="text-xs text-muted-foreground">{seg.description || 'No description.'}</span>
                                                    </div>
                                                </td>
                                                <td className="p-4 text-xs font-mono text-muted-foreground">
                                                    {Object.entries(seg.filters || {})
                                                        .filter(([_, v]) => !!v)
                                                        .map(([k, v]) => `${k}:${v}`)
                                                        .join(', ') || 'All Users'}
                                                </td>
                                                <td className="p-4 font-semibold text-foreground">
                                                    {seg.member_count} users
                                                </td>
                                                <td className="p-4 text-muted-foreground">
                                                    {new Date(seg.created_at).toLocaleDateString()}
                                                </td>
                                                <td className="p-4 text-right space-x-1 whitespace-nowrap">
                                                    <a href={`/admin/segments/${seg.id}/export`} className="inline-block">
                                                        <Button variant="outline" size="sm" className="h-8 text-xs cursor-pointer">
                                                            <Download className="h-3 w-3 mr-1" /> Export CSV
                                                        </Button>
                                                    </a>
                                                    <Link href={`/admin/segments/${seg.id}/notify`} className="inline-block">
                                                        <Button variant="outline" size="sm" className="h-8 text-xs cursor-pointer">
                                                            <Bell className="h-3 w-3 mr-1" /> Notify
                                                        </Button>
                                                    </Link>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => handleDelete(seg.id)}
                                                        className="h-8 w-8 text-muted-foreground hover:text-rose-500 cursor-pointer"
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
                    </CardContent>
                </Card>

                {/* Create segment modal */}
                <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Create Dynamic Segment</DialogTitle>
                            <DialogDescription>
                                Set filter parameters. The segment count will evaluate dynamically.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleSubmit} className="space-y-4 py-2">
                            <div className="grid gap-2">
                                <Label htmlFor="seg-name">Segment Name</Label>
                                <Input
                                    id="seg-name"
                                    placeholder="e.g. Active Pro Users"
                                    value={form.data.name}
                                    onChange={(e) => form.setData('name', e.target.value)}
                                    required
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="seg-desc">Description</Label>
                                <Input
                                    id="seg-desc"
                                    placeholder="Brief description..."
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                />
                            </div>

                            {/* Dynamic Filters */}
                            <div className="space-y-3 border-t border-border pt-3">
                                <h4 className="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1">
                                    <Filter className="h-3 w-3" /> Filters
                                </h4>
                                <div className="grid grid-cols-3 gap-2">
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="filt-role" className="text-xs">User Role</Label>
                                        <select
                                            id="filt-role"
                                            value={form.data.filters.role}
                                            onChange={(e) => handleFilterChange('role', e.target.value)}
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-2 py-1 text-xs"
                                        >
                                            <option value="">Any Role</option>
                                            <option value="Admin">Admin</option>
                                            <option value="Super Admin">Super Admin</option>
                                            <option value="User">User</option>
                                        </select>
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="filt-plan" className="text-xs">SaaS Plan</Label>
                                        <select
                                            id="filt-plan"
                                            value={form.data.filters.plan}
                                            onChange={(e) => handleFilterChange('plan', e.target.value)}
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-2 py-1 text-xs"
                                        >
                                            <option value="">Any Plan</option>
                                            <option value="free-starter">Free Starter</option>
                                            <option value="pro-monthly">Pro Monthly</option>
                                            <option value="pro-yearly">Pro Yearly</option>
                                            <option value="enterprise-monthly">Enterprise Monthly</option>
                                            <option value="enterprise-yearly">Enterprise Yearly</option>
                                            <option value="lifetime">Lifetime</option>
                                        </select>
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="filt-susp" className="text-xs">Suspension</Label>
                                        <select
                                            id="filt-susp"
                                            value={form.data.filters.is_suspended}
                                            onChange={(e) => handleFilterChange('is_suspended', e.target.value)}
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-2 py-1 text-xs"
                                        >
                                            <option value="">Any Status</option>
                                            <option value="false">Active Only</option>
                                            <option value="true">Suspended Only</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div className="bg-muted/40 border border-border p-3 rounded-xl flex items-center justify-between text-xs">
                                <div>
                                    <span className="font-semibold text-muted-foreground">Preview Matches</span>
                                    <p className="font-bold text-foreground text-sm mt-0.5">
                                        {previewCount !== null ? `${previewCount} users` : 'Click evaluate to count'}
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={handlePreview}
                                    disabled={previewing}
                                    className="cursor-pointer"
                                >
                                    {previewing && <Loader2 className="mr-1 h-3.5 w-3.5 animate-spin" />}
                                    <Play className="h-3 w-3 mr-1" /> Evaluate
                                </Button>
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setCreateOpen(false)}>Cancel</Button>
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    Save Segment
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

SegmentsIndex.layout = {
    breadcrumbs: [
        {
            title: 'User Segments',
            href: '/admin/segments',
        },
    ],
};
