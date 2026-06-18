import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { FileText, Edit2 } from 'lucide-react';

interface TemplateItem {
    id: number;
    key: string;
    name: string;
    subject: string;
    is_active: boolean;
}

interface Props {
    templates: {
        data: TemplateItem[];
        links: any[];
        total: number;
        current_page: number;
        last_page: number;
    };
}

export default function TemplatesIndex({ templates }: Props) {
    return (
        <>
            <Head title="Email Templates" />

            <div className="flex-1 space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-foreground">Email Templates</h1>
                    <p className="text-muted-foreground text-sm">
                        View and update dynamic transactional or notification mailing templates.
                    </p>
                </div>

                <Card className="border border-border">
                    <CardHeader>
                        <CardTitle className="text-base font-bold">Mail Layouts Directory</CardTitle>
                        <CardDescription>
                            Edit dynamic variables (e.g. &#123;user_name&#125;, &#123;otp_code&#125;) for system emails.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr className="bg-muted/40 font-bold text-muted-foreground border-b border-border">
                                        <th className="p-4">Template Name</th>
                                        <th className="p-4">Template Key</th>
                                        <th className="p-4">Subject Line</th>
                                        <th className="p-4">Status</th>
                                        <th className="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {templates.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="p-8 text-center text-muted-foreground">
                                                No email templates found.
                                            </td>
                                        </tr>
                                    ) : (
                                        templates.data.map((item) => (
                                            <tr key={item.id} className="hover:bg-muted/10 transition-colors">
                                                <td className="p-4 font-semibold text-foreground flex items-center gap-2">
                                                    <FileText className="h-4 w-4 text-primary" />
                                                    {item.name}
                                                </td>
                                                <td className="p-4 font-mono text-xs text-muted-foreground">{item.key}</td>
                                                <td className="p-4 text-muted-foreground max-w-xs truncate">{item.subject}</td>
                                                <td className="p-4">
                                                    <Badge variant="outline" className={item.is_active ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-neutral-500/10 text-neutral-500 border-neutral-500/20'}>
                                                        {item.is_active ? 'Active' : 'Inactive'}
                                                    </Badge>
                                                </td>
                                                <td className="p-4 text-right">
                                                    <Link href={`/admin/email-templates/${item.id}/edit`}>
                                                        <Button variant="outline" size="sm" className="h-8 text-xs cursor-pointer">
                                                            <Edit2 className="h-3 w-3 mr-1" /> Edit Content
                                                        </Button>
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {templates.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-border px-4 py-3 sm:px-6">
                                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                    <p className="text-sm text-muted-foreground">
                                        Showing page <span className="font-semibold text-foreground">{templates.current_page}</span> of{' '}
                                        <span className="font-semibold text-foreground">{templates.last_page}</span> ({templates.total} total templates)
                                    </p>
                                    <nav className="isolate inline-flex -space-x-px rounded-md shadow-xs" aria-label="Pagination">
                                        {templates.links.map((link: any, idx: number) => (
                                            <Link
                                                key={idx}
                                                href={link.url || '#'}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                className={`relative inline-flex items-center px-4 py-2 text-sm font-semibold border border-border focus:z-20 ${
                                                    link.active
                                                        ? 'z-10 bg-primary text-primary-foreground focus-visible:outline-2'
                                                        : 'text-foreground hover:bg-accent bg-background'
                                                } ${!link.url ? 'pointer-events-none opacity-50' : ''} ${
                                                    idx === 0 ? 'rounded-l-md' : idx === templates.links.length - 1 ? 'rounded-r-md' : ''
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

TemplatesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Email Templates',
            href: '/admin/email-templates',
        },
    ],
};
