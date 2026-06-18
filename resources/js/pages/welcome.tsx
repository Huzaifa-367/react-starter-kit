import { Head, Link, usePage } from '@inertiajs/react';
import { dashboard, login, register } from '@/routes';

const features = [
    {
        icon: '⚡',
        title: 'Blazing Fast',
        desc: 'Built on Laravel 11 + React 19 with Vite for sub-second hot reloads and lightning-fast production builds.',
    },
    {
        icon: '🔐',
        title: 'Auth & 2FA',
        desc: 'Email OTP, magic links, social OAuth, passkeys, and TOTP two-factor — all production-ready out of the box.',
    },
    {
        icon: '💳',
        title: 'Stripe Billing',
        desc: 'Subscription plans, proration, trial periods, dunning, coupons, and a self-serve billing portal.',
    },
    {
        icon: '🛡️',
        title: 'Role-Based Access',
        desc: 'Spatie permissions with fine-grained feature flags, IP firewall, and rate-limit controls per user segment.',
    },
    {
        icon: '📊',
        title: 'Admin Dashboard',
        desc: 'Full analytics, broadcast notifications, activity logs, failed jobs, and real-time system health monitoring.',
    },
    {
        icon: '📱',
        title: 'PWA-Ready',
        desc: 'Installable on any device with offline fallback, service worker caching, and push notification support.',
    },
];

const stats = [
    { label: 'Features shipped', value: '40+' },
    { label: 'Auth methods', value: '6' },
    { label: 'Admin panels', value: '15+' },
    { label: 'Tests passing', value: '39' },
];

export default function Welcome() {
    const { auth } = usePage<{ auth: { user: object | null } }>().props;

    return (
        <>
            <Head title="SaaS Starter Kit — Laravel + React" />

            <div className="min-h-screen bg-[#0a0a0f] text-white antialiased overflow-x-hidden">

                {/* ── NAV ──────────────────────────────────────────────────── */}
                <header className="fixed top-0 inset-x-0 z-50 border-b border-white/5 bg-[#0a0a0f]/80 backdrop-blur-xl">
                    <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                        {/* Logo */}
                        <div className="flex items-center gap-3">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-violet-500 to-indigo-600 shadow-lg shadow-violet-500/30">
                                <svg viewBox="0 0 24 24" className="h-4 w-4 fill-white">
                                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="white" strokeWidth="2" fill="none" strokeLinecap="round" strokeLinejoin="round"/>
                                </svg>
                            </div>
                            <span className="font-semibold tracking-tight">SaaS Starter</span>
                        </div>

                        {/* Nav links */}
                        <nav className="hidden items-center gap-8 text-sm text-white/60 md:flex">
                            <a href="#features" className="transition-colors hover:text-white">Features</a>
                            <a href="#stats" className="transition-colors hover:text-white">Stats</a>
                            <Link href={'/pricing'} className="transition-colors hover:text-white">Pricing</Link>
                        </nav>

                        {/* Auth buttons */}
                        <div className="flex items-center gap-3">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white transition-all hover:bg-violet-500 hover:shadow-lg hover:shadow-violet-500/25"
                                >
                                    Dashboard →
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="rounded-lg px-4 py-2 text-sm font-medium text-white/70 transition-colors hover:text-white"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href={register()}
                                        className="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-violet-500/25 transition-all hover:shadow-violet-500/40 hover:brightness-110"
                                    >
                                        Get started →
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </header>

                {/* ── HERO ─────────────────────────────────────────────────── */}
                <section className="relative flex min-h-screen flex-col items-center justify-center px-6 pt-16 text-center">
                    {/* Gradient orbs */}
                    <div className="pointer-events-none absolute inset-0 overflow-hidden">
                        <div className="absolute -top-40 left-1/2 h-[600px] w-[600px] -translate-x-1/2 rounded-full bg-violet-600/20 blur-3xl" />
                        <div className="absolute top-20 right-0 h-[400px] w-[400px] rounded-full bg-indigo-600/15 blur-3xl" />
                        <div className="absolute bottom-0 left-0 h-[350px] w-[350px] rounded-full bg-fuchsia-600/10 blur-3xl" />
                    </div>

                    {/* Badge */}
                    <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-violet-500/30 bg-violet-500/10 px-4 py-1.5 text-xs font-medium text-violet-300">
                        <span className="relative flex h-2 w-2">
                            <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-violet-400 opacity-75" />
                            <span className="relative inline-flex h-2 w-2 rounded-full bg-violet-500" />
                        </span>
                        Production-ready SaaS boilerplate
                    </div>

                    {/* Headline */}
                    <h1 className="relative max-w-4xl text-5xl font-bold leading-tight tracking-tight md:text-7xl">
                        <span className="bg-gradient-to-b from-white to-white/60 bg-clip-text text-transparent">
                            Ship your SaaS
                        </span>
                        <br />
                        <span className="bg-gradient-to-r from-violet-400 via-fuchsia-400 to-indigo-400 bg-clip-text text-transparent">
                            in days, not months.
                        </span>
                    </h1>

                    <p className="relative mt-6 max-w-2xl text-lg leading-relaxed text-white/50">
                        A complete Laravel + React starter kit with authentication, Stripe billing,
                        role-based access, admin dashboards, feature flags, and more — all production-ready.
                    </p>

                    {/* CTA buttons */}
                    <div className="relative mt-10 flex flex-wrap items-center justify-center gap-4">
                        <Link
                            href={auth.user ? dashboard() : register()}
                            className="group relative inline-flex items-center gap-2 overflow-hidden rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-8 py-3.5 text-base font-semibold text-white shadow-2xl shadow-violet-500/30 transition-all hover:shadow-violet-500/50 hover:brightness-110"
                        >
                            <span>{auth.user ? 'Go to Dashboard' : 'Start for free'}</span>
                            <svg className="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </Link>

                        <a
                            href="#features"
                            className="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-8 py-3.5 text-base font-medium text-white/80 backdrop-blur-sm transition-all hover:border-white/20 hover:bg-white/10 hover:text-white"
                        >
                            See features
                        </a>
                    </div>

                    {/* Social proof */}
                    <p className="relative mt-8 text-sm text-white/30">
                        No credit card required · Deploy anywhere · MIT license
                    </p>

                    {/* Hero card preview */}
                    <div className="relative mt-20 w-full max-w-5xl">
                        <div className="overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-0.5 shadow-2xl shadow-black/50 backdrop-blur-sm">
                            <div className="rounded-[14px] bg-[#111117] p-6">
                                {/* Fake browser chrome */}
                                <div className="mb-4 flex items-center gap-2">
                                    <div className="h-3 w-3 rounded-full bg-red-500/70" />
                                    <div className="h-3 w-3 rounded-full bg-yellow-500/70" />
                                    <div className="h-3 w-3 rounded-full bg-green-500/70" />
                                    <div className="ml-3 flex-1 rounded-md bg-white/5 px-3 py-1 text-xs text-white/30">
                                        https://app.yourproduct.com/dashboard
                                    </div>
                                </div>

                                {/* Dashboard preview */}
                                <div className="grid grid-cols-4 gap-3">
                                    {[
                                        { label: 'MRR', value: '$24,891', change: '+12.3%', color: 'from-violet-500 to-indigo-500' },
                                        { label: 'Active Users', value: '1,429', change: '+8.1%', color: 'from-fuchsia-500 to-violet-500' },
                                        { label: 'Churn Rate', value: '2.1%', change: '-0.4%', color: 'from-cyan-500 to-blue-500' },
                                        { label: 'Subscriptions', value: '348', change: '+22', color: 'from-emerald-500 to-teal-500' },
                                    ].map((stat) => (
                                        <div key={stat.label} className="rounded-xl border border-white/5 bg-white/[0.03] p-4">
                                            <p className="text-xs text-white/40">{stat.label}</p>
                                            <p className="mt-1 text-xl font-bold text-white">{stat.value}</p>
                                            <p className={`mt-1 text-xs font-medium bg-gradient-to-r ${stat.color} bg-clip-text text-transparent`}>{stat.change}</p>
                                        </div>
                                    ))}
                                </div>

                                <div className="mt-3 h-32 rounded-xl border border-white/5 bg-white/[0.03] p-4 flex items-end gap-1">
                                    {[40, 65, 45, 70, 55, 80, 60, 90, 75, 95, 70, 100].map((h, i) => (
                                        <div
                                            key={i}
                                            className="flex-1 rounded-sm bg-gradient-to-t from-violet-600 to-indigo-400 opacity-80"
                                            style={{ height: `${h}%` }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Glow under card */}
                        <div className="absolute -bottom-10 left-1/2 h-40 w-3/4 -translate-x-1/2 rounded-full bg-violet-600/20 blur-3xl" />
                    </div>
                </section>

                {/* ── STATS ────────────────────────────────────────────────── */}
                <section id="stats" className="relative py-20">
                    <div className="mx-auto max-w-5xl px-6">
                        <div className="grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-white/5 bg-white/5 md:grid-cols-4">
                            {stats.map((s) => (
                                <div key={s.label} className="bg-[#0a0a0f] p-8 text-center">
                                    <p className="bg-gradient-to-r from-violet-400 to-indigo-400 bg-clip-text text-4xl font-bold text-transparent">{s.value}</p>
                                    <p className="mt-2 text-sm text-white/40">{s.label}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ── FEATURES ─────────────────────────────────────────────── */}
                <section id="features" className="relative py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="text-4xl font-bold tracking-tight text-white md:text-5xl">
                                Everything you need,
                                <br />
                                <span className="bg-gradient-to-r from-violet-400 to-indigo-400 bg-clip-text text-transparent">nothing you don't.</span>
                            </h2>
                            <p className="mx-auto mt-4 max-w-xl text-white/50">
                                Skip months of boilerplate. Every feature is architected for scale and built to production standards.
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {features.map((f) => (
                                <div
                                    key={f.title}
                                    className="group relative overflow-hidden rounded-2xl border border-white/5 bg-white/[0.03] p-6 transition-all duration-300 hover:border-violet-500/30 hover:bg-white/[0.06] hover:shadow-xl hover:shadow-violet-500/10"
                                >
                                    <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-2xl">
                                        {f.icon}
                                    </div>
                                    <h3 className="mb-2 font-semibold text-white">{f.title}</h3>
                                    <p className="text-sm leading-relaxed text-white/50">{f.desc}</p>

                                    {/* Hover gradient */}
                                    <div className="absolute inset-0 rounded-2xl bg-gradient-to-br from-violet-600/5 to-transparent opacity-0 transition-opacity group-hover:opacity-100" />
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ── CTA ──────────────────────────────────────────────────── */}
                <section className="relative py-24">
                    <div className="mx-auto max-w-4xl px-6 text-center">
                        <div className="relative overflow-hidden rounded-3xl border border-violet-500/20 bg-gradient-to-br from-violet-950/60 to-indigo-950/60 p-12 backdrop-blur-sm">
                            {/* BG glow */}
                            <div className="pointer-events-none absolute inset-0">
                                <div className="absolute -top-20 left-1/2 h-60 w-60 -translate-x-1/2 rounded-full bg-violet-600/20 blur-3xl" />
                            </div>

                            <h2 className="relative text-3xl font-bold tracking-tight text-white md:text-5xl">
                                Ready to launch your SaaS?
                            </h2>
                            <p className="relative mx-auto mt-4 max-w-xl text-white/50">
                                Start with a solid foundation. Customise, deploy, and grow — without reinventing the wheel.
                            </p>

                            <div className="relative mt-8 flex flex-wrap items-center justify-center gap-4">
                                <Link
                                    href={auth.user ? dashboard() : register()}
                                    className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-8 py-3.5 text-base font-semibold text-white shadow-2xl shadow-violet-500/30 transition-all hover:brightness-110"
                                >
                                    {auth.user ? 'Go to Dashboard' : 'Get started for free'}
                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>

                {/* ── FOOTER ───────────────────────────────────────────────── */}
                <footer className="border-t border-white/5 py-10">
                    <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 md:flex-row">
                        <div className="flex items-center gap-2">
                            <div className="flex h-6 w-6 items-center justify-center rounded-md bg-gradient-to-br from-violet-500 to-indigo-600">
                                <svg viewBox="0 0 24 24" className="h-3 w-3 fill-white">
                                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="white" strokeWidth="2" fill="none" strokeLinecap="round" strokeLinejoin="round"/>
                                </svg>
                            </div>
                            <span className="text-sm font-medium text-white/60">SaaS Starter Kit</span>
                        </div>

                        <p className="text-sm text-white/30">
                            Built with Laravel + React. MIT License.
                        </p>

                        <div className="flex items-center gap-6 text-sm text-white/40">
                            <Link href={login()} className="hover:text-white/70 transition-colors">Login</Link>
                            <Link href={register()} className="hover:text-white/70 transition-colors">Register</Link>
                            <Link href={'/pricing'} className="hover:text-white/70 transition-colors">Pricing</Link>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
