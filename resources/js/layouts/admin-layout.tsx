import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarGroupContent,
} from '@/components/ui/sidebar';
import AppLogo from '@/components/app-logo';
import {
    LayoutGrid,
    BarChart3,
    Users,
    ShieldCheck,
    CreditCard,
    MailOpen,
    Tag,
    Filter,
    Radio,
    FileText,
    ToggleLeft,
    Lock,
    Sliders,
    History,
    Webhook,
    AlertTriangle,
    HeartPulse,
    Activity,
    Terminal,
    Home,
} from 'lucide-react';

interface BreadcrumbItem {
    title: string;
    href: string;
}

interface AdminLayoutProps {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default function AdminLayout({ children, breadcrumbs = [] }: AdminLayoutProps) {
    const currentUrl = usePage().url;

    const navSections = [
        {
            label: 'Core',
            items: [
                { title: 'Dashboard', href: '/admin/dashboard', icon: LayoutGrid },
                { title: 'Analytics', href: '/admin/analytics', icon: BarChart3 },
                { title: 'Settings', href: '/admin/settings', icon: Sliders },
            ],
        },
        {
            label: 'Management',
            items: [
                { title: 'Users', href: '/admin/users', icon: Users },
                { title: 'Roles', href: '/admin/roles', icon: ShieldCheck },
                { title: 'Plans', href: '/admin/plans', icon: CreditCard },
                { title: 'Invitations', href: '/admin/invitations', icon: MailOpen },
                { title: 'Coupons', href: '/admin/coupons', icon: Tag },
                { title: 'Segments', href: '/admin/segments', icon: Filter },
            ],
        },
        {
            label: 'Communication',
            items: [
                { title: 'Broadcasts', href: '/admin/broadcasts', icon: Radio },
                { title: 'Email Templates', href: '/admin/email-templates', icon: FileText },
            ],
        },
        {
            label: 'System Operations',
            items: [
                { title: 'Feature Flags', href: '/admin/feature-flags', icon: ToggleLeft },
                { title: 'IP Rules', href: '/admin/ip-rules', icon: Lock },
                { title: 'Webhook Logs', href: '/admin/webhook-logs', icon: Webhook },
                { title: 'Failed Jobs', href: '/admin/failed-jobs', icon: AlertTriangle },
                { title: 'System Health', href: '/admin/system-health', icon: HeartPulse },
                { title: 'Rate Limits', href: '/admin/rate-limits', icon: Activity },
                { title: 'Log Viewer', href: '/admin/logs', icon: Terminal },
                { title: 'Activity Log', href: '/admin/activity', icon: History },
            ],
        },
    ];

    const isActive = (href: string) => {
        if (href === '/admin/dashboard') {
            return currentUrl === href;
        }
        return currentUrl.startsWith(href);
    };

    return (
        <AppShell variant="sidebar">
            <Sidebar collapsible="icon" variant="inset">
                <SidebarHeader>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" asChild>
                                <Link href="/admin/dashboard">
                                    <div className="flex items-center gap-2">
                                        <AppLogo />
                                        <div className="flex flex-col text-left">
                                            <span className="font-bold text-xs leading-none">Admin Panel</span>
                                            <span className="text-[10px] text-muted-foreground">SaaS Management</span>
                                        </div>
                                    </div>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarHeader>

                <SidebarContent>
                    {/* Return to App link */}
                    <SidebarGroup>
                        <SidebarGroupContent>
                            <SidebarMenu>
                                <SidebarMenuItem>
                                    <SidebarMenuButton asChild>
                                        <Link href="/dashboard" className="text-primary hover:text-primary/95 font-semibold">
                                            <Home className="h-4 w-4" />
                                            <span>Return to App</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            </SidebarMenu>
                        </SidebarGroupContent>
                    </SidebarGroup>

                    {navSections.map((section) => (
                        <SidebarGroup key={section.label}>
                            <SidebarGroupLabel className="text-[10px] font-bold tracking-wider uppercase text-muted-foreground">
                                {section.label}
                            </SidebarGroupLabel>
                            <SidebarGroupContent>
                                <SidebarMenu>
                                    {section.items.map((item) => {
                                        const Icon = item.icon;
                                        const active = isActive(item.href);
                                        return (
                                            <SidebarMenuItem key={item.title}>
                                                <SidebarMenuButton asChild isActive={active}>
                                                    <Link href={item.href}>
                                                        <Icon className={`h-4 w-4 ${active ? 'text-primary' : 'text-muted-foreground'}`} />
                                                        <span>{item.title}</span>
                                                    </Link>
                                                </SidebarMenuButton>
                                            </SidebarMenuItem>
                                        );
                                    })}
                                </SidebarMenu>
                            </SidebarGroupContent>
                        </SidebarGroup>
                    ))}
                </SidebarContent>

                <SidebarFooter>
                    <NavUser />
                </SidebarFooter>
            </Sidebar>

            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
