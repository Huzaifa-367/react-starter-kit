import { createInertiaApp, usePage, Link } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import AdminLayout from '@/layouts/admin-layout';
import ImpersonationBar from '@/components/impersonation-bar';
import { Button } from '@/components/ui/button';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const PricingLayoutWrapper = ({ children }: { children: React.ReactNode }) => {
    const { props } = usePage<any>();
    const user = props.auth?.user;
    
    const activeSubscription = props.activeSubscription;
    const isStaff = user?.roles?.some((r: any) => r.name === 'Admin' || r.name === 'Super Admin') || false;

    if (activeSubscription || isStaff) {
        return <AppLayout>{children}</AppLayout>;
    }

    return (
        <div className="min-h-screen bg-background flex flex-col">
            <header className="border-b border-border bg-card">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                    <Link href="/" className="flex items-center gap-3">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-violet-500 to-indigo-600 shadow-lg shadow-violet-500/30">
                            <svg viewBox="0 0 24 24" className="h-4 w-4 fill-white">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="white" strokeWidth="2" fill="none" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                        </div>
                        <span className="font-semibold tracking-tight text-foreground">SaaS Starter</span>
                    </Link>

                    <div className="flex items-center gap-3">
                        {user ? (
                            <>
                                <span className="hidden sm:inline text-sm text-muted-foreground mr-2">Logged in as {user.email}</span>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href="/logout" method="post" as="button">
                                        Log out
                                    </Link>
                                </Button>
                            </>
                        ) : (
                            <>
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href="/login">Log in</Link>
                                </Button>
                                <Button size="sm" asChild>
                                    <Link href="/register">Register</Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>
            </header>
            <main className="flex-1">{children}</main>
        </div>
    );
};

const ImpersonationWrapper = ({ children }: { children: React.ReactNode }) => {
    return (
        <>
            <ImpersonationBar />
            {children}
        </>
    );
};

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        let layout;
        switch (true) {
            case name === 'welcome':
                layout = null;
                break;
            case name === 'billing/pricing':
                layout = PricingLayoutWrapper;
                break;
            case name.startsWith('auth/'):
                layout = AuthLayout;
                break;
            case name.startsWith('settings/'):
                layout = [AppLayout, SettingsLayout];
                break;
            case name.startsWith('admin/'):
                layout = AdminLayout;
                break;
            default:
                layout = AppLayout;
                break;
        }

        if (Array.isArray(layout)) {
            return [ImpersonationWrapper, ...layout];
        } else if (layout) {
            return [ImpersonationWrapper, layout];
        } else {
            return ImpersonationWrapper;
        }
    },
    strictMode: true,
    setup({ el, App, props }) {
        const root = (window as any).__reactRoot || createRoot(el);
        if (!(window as any).__reactRoot) {
            (window as any).__reactRoot = root;
        }
        root.render(
            <TooltipProvider delayDuration={0}>
                <App {...props} />
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
