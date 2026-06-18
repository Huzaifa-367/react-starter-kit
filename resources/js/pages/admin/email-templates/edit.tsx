import React, { useState, useEffect } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
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
import { ArrowLeft, Loader2, Play, Send, RefreshCw, Eye } from 'lucide-react';
import { toast } from 'sonner';
import { apiGet, apiPost } from '@/lib/api';

interface Template {
    id: number;
    key: string;
    name: string;
    subject: string;
    body_html: string;
    body_text: string | null;
    is_active: boolean;
}

interface Props {
    template: Template;
}

export default function TemplateEdit({ template }: Props) {
    const [previewHtml, setPreviewHtml] = useState<string>('');
    const [previewLoading, setPreviewLoading] = useState(false);
    const [testEmail, setTestEmail] = useState('');
    const [testingEmail, setTestingEmail] = useState(false);

    const form = useForm({
        subject: template.subject,
        body_html: template.body_html,
        body_text: template.body_text || '',
        is_active: template.is_active,
    });

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/admin/email-templates/${template.id}`, {
            onSuccess: () => {
                toast.success('Email template updated successfully.');
            },
            onError: (err) => {
                toast.error(Object.values(err)[0] as string || 'Failed to update template.');
            },
        });
    };

    const fetchPreview = async () => {
        setPreviewLoading(true);
        try {
            const { data } = await apiGet<{ html: string }>(`/admin/email-templates/${template.id}/preview`);
            setPreviewHtml(data.html);
        } catch (error) {
            toast.error('Failed to generate template preview.');
        } finally {
            setPreviewLoading(false);
        }
    };

    const handleSendTest = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!testEmail) return;
        setTestingEmail(true);
        try {
            const { data } = await apiPost<{ message?: string }>(`/admin/email-templates/${template.id}/test`, {
                email: testEmail,
            });
            toast.success(data.message || 'Test email sent successfully!');
        } catch (error: any) {
            toast.error(error.response?.data?.error || 'Failed to send test email.');
        } finally {
            setTestingEmail(false);
        }
    };

    // Load preview on mount
    useEffect(() => {
        fetchPreview();
    }, []);

    return (
        <>
            <Head title={`Edit Template: ${template.name}`} />

            <div className="flex-1 space-y-6 p-6">
                <div className="flex items-center gap-3 border-b border-border pb-4">
                    <Link href="/admin/email-templates">
                        <Button variant="ghost" size="icon" className="h-8 w-8 cursor-pointer">
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Edit Template: {template.name}
                        </h1>
                        <p className="text-xs text-muted-foreground">
                            Modify subject line, markup template body, and test rendering.
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                    {/* Left: Editor Form */}
                    <div className="space-y-6">
                        <Card className="border border-border">
                            <CardHeader>
                                <CardTitle className="text-base font-bold">Template Editors</CardTitle>
                            </CardHeader>
                            <form onSubmit={handleUpdate}>
                                <CardContent className="space-y-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="subject">Subject Line</Label>
                                        <Input
                                            id="subject"
                                            value={form.data.subject}
                                            onChange={(e) => form.setData('subject', e.target.value)}
                                            required
                                            disabled={form.processing}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="body_html">HTML Body Markup</Label>
                                        <textarea
                                            id="body_html"
                                            rows={12}
                                            value={form.data.body_html}
                                            onChange={(e) => form.setData('body_html', e.target.value)}
                                            required
                                            disabled={form.processing}
                                            className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm font-mono ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring"
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="body_text">Plain Text Body (Optional Fallback)</Label>
                                        <textarea
                                            id="body_text"
                                            rows={4}
                                            value={form.data.body_text}
                                            onChange={(e) => form.setData('body_text', e.target.value)}
                                            disabled={form.processing}
                                            className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground"
                                        />
                                    </div>

                                    <div className="flex items-center space-x-2 pt-2">
                                        <input
                                            type="checkbox"
                                            id="is_active"
                                            checked={form.data.is_active}
                                            onChange={(e) => form.setData('is_active', e.target.checked)}
                                            className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                        />
                                        <Label htmlFor="is_active" className="text-sm font-medium">
                                            Template is active and available for use
                                        </Label>
                                    </div>
                                </CardContent>
                                <CardFooter className="border-t border-border pt-4 pb-4 flex justify-between">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={fetchPreview}
                                        className="cursor-pointer"
                                    >
                                        <RefreshCw className="mr-1.5 h-4 w-4" />
                                        Refresh Preview
                                    </Button>
                                    <Button type="submit" disabled={form.processing} className="cursor-pointer">
                                        {form.processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                        Save Template
                                    </Button>
                                </CardFooter>
                            </form>
                        </Card>

                        {/* Send Test Email Card */}
                        <Card className="border border-border">
                            <CardHeader>
                                <CardTitle className="text-sm font-bold flex items-center gap-1.5">
                                    <Send className="h-4 w-4 text-primary" />
                                    Send Test Email
                                </CardTitle>
                                <CardDescription>
                                    Dispatch a mock render of this template to verify SMTP configurations.
                                </CardDescription>
                            </CardHeader>
                            <form onSubmit={handleSendTest}>
                                <CardContent>
                                    <div className="grid gap-2">
                                        <Label htmlFor="test-email">Recipient Email Address</Label>
                                        <Input
                                            id="test-email"
                                            type="email"
                                            placeholder="test@example.com"
                                            value={testEmail}
                                            onChange={(e) => setTestEmail(e.target.value)}
                                            required
                                            disabled={testingEmail}
                                        />
                                    </div>
                                </CardContent>
                                <CardFooter className="border-t border-border pt-4 pb-4 bg-muted/10">
                                    <Button type="submit" disabled={testingEmail || !testEmail} size="sm" className="ml-auto cursor-pointer">
                                        {testingEmail && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                        Send Test
                                    </Button>
                                </CardFooter>
                            </form>
                        </Card>
                    </div>

                    {/* Right: Render Preview */}
                    <Card className="border border-border h-[650px] flex flex-col">
                        <CardHeader className="flex flex-row items-center justify-between pb-3 border-b border-border">
                            <div className="space-y-0.5">
                                <CardTitle className="text-base font-bold flex items-center gap-2">
                                    <Eye className="h-5 w-5 text-primary" />
                                    Live Render Preview
                                </CardTitle>
                                <CardDescription>HTML output with mock variables.</CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent className="flex-1 p-0 overflow-hidden relative bg-neutral-50 dark:bg-neutral-900/30">
                            {previewLoading ? (
                                <div className="absolute inset-0 flex items-center justify-center bg-background/50">
                                    <Loader2 className="h-8 w-8 animate-spin text-primary" />
                                </div>
                            ) : previewHtml ? (
                                <iframe
                                    title="Email Preview"
                                    srcDoc={previewHtml}
                                    className="w-full h-full border-0"
                                />
                            ) : (
                                <div className="absolute inset-0 flex items-center justify-center text-muted-foreground text-sm">
                                    No preview generated. Click Refresh Preview.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

TemplateEdit.layout = {
    breadcrumbs: [
        { title: 'Email Templates', href: '/admin/email-templates' },
        { title: 'Edit Template', href: '#' },
    ],
};
