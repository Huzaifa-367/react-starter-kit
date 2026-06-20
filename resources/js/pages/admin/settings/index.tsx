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
import { Sliders, Mail, Smartphone, CreditCard, Bell, Sparkles, Loader2, Save, RotateCw } from 'lucide-react';
import { toast } from 'sonner';

interface SettingItem {
    id: number;
    key: string;
    value: string | null;
    type: string;
    group: string;
    label: string;
    is_encrypted: boolean;
    is_public: boolean;
}

interface Props {
    settings: Record<string, SettingItem[]>;
}

export default function SettingsIndex({ settings }: Props) {
    const groups = Object.keys(settings);
    const [activeGroup, setActiveGroup] = useState<string>(groups[0] || 'app');
    const [syncing, setSyncing] = useState<boolean>(false);
    const [origin, setOrigin] = useState<string>('http://your-domain.com');

    useEffect(() => {
        if (typeof window !== 'undefined') {
            setOrigin(window.location.origin);
        }
    }, []);

    const handleSyncWhatsapp = () => {
        setSyncing(true);
        router.post('/admin/settings/sync-whatsapp', {}, {
            onSuccess: () => {
                toast.success('WhatsApp session settings synchronized successfully!');
                setSyncing(false);
            },
            onError: (err) => {
                toast.error(Object.values(err)[0] as string || 'Failed to sync WhatsApp session.');
                setSyncing(false);
            },
            onFinish: () => {
                setSyncing(false);
            }
        });
    };

    const [testEmail, setTestEmail] = useState<string>('');
    const [testPhone, setTestPhone] = useState<string>('');
    const [sendingTestMail, setSendingTestMail] = useState<boolean>(false);
    const [sendingTestWhatsapp, setSendingTestWhatsapp] = useState<boolean>(false);

    const handleSendTestEmail = () => {
        setSendingTestMail(true);
        router.post('/admin/settings/test-email', { email: testEmail }, {
            onSuccess: () => {
                toast.success('Test email sent successfully!');
                setSendingTestMail(false);
            },
            onError: (err) => {
                toast.error(Object.values(err)[0] as string || 'Failed to send test email.');
                setSendingTestMail(false);
            },
            onFinish: () => {
                setSendingTestMail(false);
            }
        });
    };

    const handleSendTestWhatsapp = () => {
        setSendingTestWhatsapp(true);
        router.post('/admin/settings/test-whatsapp', { phone: testPhone }, {
            onSuccess: () => {
                toast.success('Test WhatsApp message sent successfully!');
                setSendingTestWhatsapp(false);
            },
            onError: (err) => {
                toast.error(Object.values(err)[0] as string || 'Failed to send test WhatsApp message.');
                setSendingTestWhatsapp(false);
            },
            onFinish: () => {
                setSendingTestWhatsapp(false);
            }
        });
    };

    // Flatten settings to construct initial form values
    const initialValues: Record<string, string> = {};
    Object.values(settings).forEach((groupSettings) => {
        groupSettings.forEach((setting) => {
            initialValues[setting.key] = setting.value || '';
        });
    });

    const form = useForm({
        settings: initialValues,
    });

    useEffect(() => {
        const updatedValues: Record<string, string> = {};
        Object.values(settings).forEach((groupSettings) => {
            groupSettings.forEach((setting) => {
                updatedValues[setting.key] = setting.value || '';
            });
        });
        form.setData('settings', updatedValues);
    }, [settings]);

    const handleValueChange = (key: string, value: string) => {
        form.setData('settings', {
            ...form.data.settings,
            [key]: value,
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/admin/settings', {
            onSuccess: () => {
                toast.success('System settings updated and configuration refreshed!');
            },
            onError: (err) => {
                toast.error(Object.values(err)[0] as string || 'Failed to update settings.');
            },
        });
    };

    const getGroupIcon = (group: string) => {
        switch (group.toLowerCase()) {
            case 'mail':
            case 'smtp':
                return <Mail className="h-4 w-4" />;
            case 'twilio':
            case 'sms':
                return <Smartphone className="h-4 w-4" />;
            case 'stripe':
            case 'billing':
                return <CreditCard className="h-4 w-4" />;
            case 'firebase':
            case 'fcm':
                return <Bell className="h-4 w-4" />;
            case 'branding':
                return <Sparkles className="h-4 w-4" />;
            default:
                return <Sliders className="h-4 w-4" />;
        }
    };

    const getGroupLabel = (group: string) => {
        switch (group.toLowerCase()) {
            case 'mail':
                return 'Email SMTP';
            case 'fcm':
                return 'Push (FCM)';
            default:
                return group.charAt(0).toUpperCase() + group.slice(1) + ' Settings';
        }
    };

    return (
        <>
            <Head title="System Settings" />

            <div className="flex-1 space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-foreground">System Settings</h1>
                    <p className="text-muted-foreground text-sm">
                        Global SaaS integration variables, API credentials, and email credentials.
                    </p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">
                    {/* Left: Tab list */}
                    <Card className="border border-border lg:col-span-1">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-bold">Categories</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1">
                            {groups.map((group) => (
                                <button
                                    key={group}
                                    type="button"
                                    onClick={() => setActiveGroup(group)}
                                    className={`w-full flex items-center gap-2.5 p-3 rounded-xl text-left text-sm font-semibold transition-all cursor-pointer ${activeGroup === group
                                            ? 'bg-primary text-primary-foreground shadow-xs'
                                            : 'text-muted-foreground hover:bg-muted/10 hover:text-foreground'
                                        }`}
                                >
                                    {getGroupIcon(group)}
                                    <span>{getGroupLabel(group)}</span>
                                </button>
                            ))}
                        </CardContent>
                    </Card>

                    {/* Right: Settings Fields Form */}
                    <Card className="border border-border lg:col-span-3">
                        <CardHeader>
                            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div>
                                    <CardTitle className="text-lg font-bold">
                                        {getGroupLabel(activeGroup)}
                                    </CardTitle>
                                    <CardDescription>
                                        Modify credential configs. Secrets are masked with dots.
                                    </CardDescription>
                                </div>
                                {activeGroup === 'green_api' && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={handleSyncWhatsapp}
                                        disabled={syncing || form.processing}
                                        className="cursor-pointer font-semibold gap-2 border-emerald-500/20 text-emerald-500 hover:text-emerald-600 hover:bg-emerald-500/10"
                                    >
                                        {syncing ? (
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                        ) : (
                                            <RotateCw className="h-4 w-4" />
                                        )}
                                        Sync Session Settings
                                    </Button>
                                )}
                            </div>
                        </CardHeader>

                        <form onSubmit={handleSubmit}>
                            <CardContent className="space-y-6">
                                {(settings[activeGroup] || []).map((setting) => {
                                    if (setting.key === 'green_api_phone') {
                                        return (
                                            <div key={setting.id} className="grid gap-2">
                                                <Label htmlFor={`sett-${setting.key}`}>{setting.label}</Label>
                                                <div className="flex items-center gap-2 max-w-xl">
                                                    <Input
                                                        id={`sett-${setting.key}`}
                                                        value={form.data.settings[setting.key] || 'Not Synced'}
                                                        disabled
                                                        className="font-mono text-sm bg-muted/50 text-muted-foreground"
                                                    />
                                                    <Badge variant="secondary" className="whitespace-nowrap">
                                                        Sync Only
                                                    </Badge>
                                                </div>
                                            </div>
                                        );
                                    }

                                    if (setting.key === 'green_api_avatar') {
                                        const avatarUrl = form.data.settings[setting.key];
                                        return (
                                            <div key={setting.id} className="grid gap-2">
                                                <Label htmlFor={`sett-${setting.key}`}>{setting.label}</Label>
                                                <div className="flex items-center gap-4 max-w-xl">
                                                    {avatarUrl ? (
                                                        <img
                                                            src={avatarUrl}
                                                            alt="WhatsApp Session Avatar"
                                                            className="w-12 h-12 rounded-full border border-border object-cover"
                                                        />
                                                    ) : (
                                                        <div className="w-12 h-12 rounded-full border border-dashed border-muted-foreground/30 flex items-center justify-center bg-muted/30 text-muted-foreground text-[10px] font-medium text-center p-1">
                                                            No Image
                                                        </div>
                                                    )}
                                                    <Input
                                                        id={`sett-${setting.key}`}
                                                        value={avatarUrl || 'Not Synced'}
                                                        disabled
                                                        className="font-mono text-sm bg-muted/50 flex-1 text-muted-foreground"
                                                    />
                                                </div>
                                            </div>
                                        );
                                    }

                                    return (
                                        <div key={setting.id} className="grid gap-2">
                                            <div className="flex items-center gap-2">
                                                <Label htmlFor={`sett-${setting.key}`}>{setting.label}</Label>
                                                {setting.is_encrypted && (
                                                    <Badge variant="outline" className="text-[9px] font-mono py-0 text-emerald-500 border-emerald-500/10">
                                                        Encrypted
                                                    </Badge>
                                                )}
                                            </div>
                                            <Input
                                                id={`sett-${setting.key}`}
                                                type={setting.type === 'secret' ? 'password' : 'text'}
                                                value={form.data.settings[setting.key]}
                                                onChange={(e) => handleValueChange(setting.key, e.target.value)}
                                                disabled={form.processing}
                                                className="max-w-xl font-mono text-sm"
                                            />
                                        </div>
                                    );
                                })}

                                {activeGroup === 'stripe' && (
                                    <div className="border-t border-border pt-6 mt-6 space-y-4">
                                        <h3 className="text-sm font-bold text-foreground flex items-center gap-2">
                                            <CreditCard className="h-4 w-4 text-primary" />
                                            Stripe Webhook Configuration Guide
                                        </h3>
                                        <p className="text-xs text-muted-foreground">
                                            Follow these steps in your Stripe Dashboard to automate subscription lifecycle actions, handle payments, and synchronise plans.
                                        </p>
                                        <div className="bg-muted/50 border border-border rounded-xl p-4 space-y-3">
                                            <div className="grid gap-1">
                                                <span className="text-xs font-bold uppercase tracking-wider text-muted-foreground">1. Webhook Endpoint URL</span>
                                                <div className="flex items-center gap-2 mt-1">
                                                    <code className="text-xs bg-background border border-border px-2.5 py-1.5 rounded-md font-mono select-all flex-1">
                                                        {origin}/stripe/webhook
                                                    </code>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => {
                                                            navigator.clipboard.writeText(`${origin}/stripe/webhook`);
                                                            toast.success('Webhook URL copied to clipboard!');
                                                        }}
                                                        className="h-8 text-xs cursor-pointer"
                                                    >
                                                        Copy
                                                    </Button>
                                                </div>
                                            </div>

                                            <div className="grid gap-1 border-t border-border pt-3">
                                                <span className="text-xs font-bold uppercase tracking-wider text-muted-foreground">2. Events to Select</span>
                                                <p className="text-xs text-muted-foreground mb-1.5">
                                                    Configure these exact events under the "Select events to listen to" section in Stripe:
                                                </p>
                                                <div className="flex flex-wrap gap-1.5">
                                                    <Badge variant="outline" className="font-mono text-[10px] bg-background/50 border-border">
                                                        checkout.session.completed
                                                    </Badge>
                                                    <Badge variant="outline" className="font-mono text-[10px] bg-background/50 border-border">
                                                        customer.subscription.updated
                                                    </Badge>
                                                    <Badge variant="outline" className="font-mono text-[10px] bg-background/50 border-border">
                                                        customer.subscription.deleted
                                                    </Badge>
                                                    <Badge variant="outline" className="font-mono text-[10px] bg-background/50 border-border">
                                                        invoice.payment_failed
                                                    </Badge>
                                                </div>
                                            </div>

                                            <div className="grid gap-1 border-t border-border pt-3">
                                                <span className="text-xs font-bold uppercase tracking-wider text-muted-foreground">3. Webhook Secret Key</span>
                                                <p className="text-xs text-muted-foreground leading-relaxed">
                                                    Once the webhook is created, click <strong>Reveal</strong> under "Signing secret" in Stripe, and paste it into the <strong>Stripe Webhook Secret</strong> field above. It usually starts with <code>whsec_...</code>.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {activeGroup === 'smtp' && (
                                    <div className="border-t border-border pt-6 mt-6 space-y-4">
                                        <h3 className="text-sm font-bold text-foreground">Test Mail Delivery</h3>
                                        <p className="text-xs text-muted-foreground">
                                            Send a test email message to verify SMTP settings.
                                        </p>
                                        <div className="flex gap-2 max-w-xl">
                                            <Input
                                                type="email"
                                                placeholder="recipient@example.com"
                                                value={testEmail}
                                                onChange={(e) => setTestEmail(e.target.value)}
                                                className="text-sm"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={handleSendTestEmail}
                                                disabled={sendingTestMail || !testEmail}
                                                className="cursor-pointer"
                                            >
                                                {sendingTestMail ? (
                                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                ) : (
                                                    <Mail className="mr-2 h-4 w-4" />
                                                )}
                                                Send Test Email
                                            </Button>
                                        </div>
                                    </div>
                                )}

                                {activeGroup === 'green_api' && (
                                    <div className="border-t border-border pt-6 mt-6 space-y-4">
                                        <h3 className="text-sm font-bold text-foreground">Test WhatsApp Delivery</h3>
                                        <p className="text-xs text-muted-foreground">
                                            Send a test text message to a WhatsApp number to verify Green API settings.
                                        </p>
                                        <div className="flex gap-2 max-w-xl">
                                            <Input
                                                type="text"
                                                placeholder="Recipient phone (e.g. 92123456789)"
                                                value={testPhone}
                                                onChange={(e) => setTestPhone(e.target.value)}
                                                className="text-sm font-mono"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={handleSendTestWhatsapp}
                                                disabled={sendingTestWhatsapp || !testPhone}
                                                className="cursor-pointer"
                                            >
                                                {sendingTestWhatsapp ? (
                                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                ) : (
                                                    <RotateCw className="mr-2 h-4 w-4" />
                                                )}
                                                Send Test Message
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                            <CardFooter className="border-t border-border pt-4 pb-4 flex justify-between bg-muted/20">
                                <span className="text-xs text-muted-foreground">
                                    SMTP config changes will execute a runtime connection test to verify.
                                </span>
                                <Button type="submit" disabled={form.processing} className="cursor-pointer">
                                    {form.processing ? (
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <Save className="mr-2 h-4 w-4" />
                                    )}
                                    Save Configurations
                                </Button>
                            </CardFooter>
                        </form>
                    </Card>
                </div>
            </div>
        </>
    );
}

SettingsIndex.layout = {
    breadcrumbs: [
        {
            title: 'System Settings',
            href: '/admin/settings',
        },
    ],
};
