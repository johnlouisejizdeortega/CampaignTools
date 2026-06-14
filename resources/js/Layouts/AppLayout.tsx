import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import type { ComponentType, MouseEvent as ReactMouseEvent, ReactNode } from 'react';
import {
    BarChart3, Bell, ChevronDown, HelpCircle, LayoutGrid, Lightbulb,
    Megaphone, Menu, Plus, RefreshCw, Search,
} from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import ThemeToggle from '@/components/ThemeToggle';
import GoogleAdsLogo from '@/components/GoogleAdsLogo';
import type { SharedProps } from '@/types';

interface AppLayoutProps {
    title?: string;
    subtitle?: string;
    authenticated?: boolean;
    children: ReactNode;
}

interface Feature {
    id: string;
    icon: ComponentType<{ className?: string }>;
    label: string;
    short: string;
    desc: string;
    href: string;
}

// The app's real, working features. Each links to the Overview and (for the
// tools) scrolls to that tool's panel via its anchor id. The result pages those
// tools submit to are mapped below so the matching item stays highlighted.
const FEATURES: Feature[] = [
    { id: 'overview', icon: LayoutGrid, label: 'Overview', short: 'Overview', desc: 'Account metrics & trend', href: '/' },
    { id: 'report', icon: BarChart3, label: 'Reports', short: 'Reports', desc: 'Pull live performance data', href: '/#report' },
    { id: 'campaigns', icon: Megaphone, label: 'Campaigns', short: 'Campaigns', desc: 'Pause a live campaign', href: '/#campaigns' },
    { id: 'optimization', icon: Lightbulb, label: 'Recommendations', short: 'Optimize', desc: 'Optimization suggestions', href: '/#optimization' },
];
const RESULT_PATH: Record<string, string> = {
    report: '/show-report',
    campaigns: '/pause-campaign',
    optimization: '/recommendations',
};

function useActiveFeature() {
    const pathname = (usePage().url ?? '/').split('?')[0].split('#')[0];
    const [hash, setHash] = useState('');
    useEffect(() => {
        const update = () => setHash(window.location.hash.replace('#', ''));
        update();
        window.addEventListener('hashchange', update);
        return () => window.removeEventListener('hashchange', update);
    }, []);

    return (f: Feature): boolean => {
        if (f.id === 'overview') return pathname === '/' && hash === '';
        if (pathname.startsWith(RESULT_PATH[f.id] ?? '\0')) return true;
        return pathname === '/' && hash === f.id;
    };
}

function TopBar({ authenticated }: { authenticated: boolean }) {
    return (
        <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b bg-card px-3 sm:px-4">
            <button className="rounded-full p-2 text-muted-foreground hover:bg-muted" aria-label="Menu">
                <Menu className="h-5 w-5" />
            </button>
            <Link href="/" className="flex items-center gap-2">
                <GoogleAdsLogo className="h-7 w-7" />
                <span className="hidden text-xl tracking-tight text-foreground sm:inline">Google Ads</span>
            </Link>
            <span className="ml-1 hidden items-center gap-1 rounded-full px-2 py-1 text-sm text-muted-foreground md:flex">
                <span className="font-medium text-foreground">Account</span>
                <ChevronDown className="h-4 w-4" />
            </span>

            <div className="mx-auto flex w-full max-w-xl items-center gap-2 rounded-full bg-muted px-4 py-2 text-muted-foreground">
                <Search className="h-4 w-4 shrink-0" />
                <input
                    className="w-full bg-transparent text-sm text-foreground placeholder:text-muted-foreground focus:outline-none"
                    placeholder="Search for a page or campaign"
                    aria-label="Search"
                />
            </div>

            <div className="flex items-center gap-1">
                <ThemeToggle />
                <button className="hidden rounded-full p-2 text-muted-foreground hover:bg-muted sm:block" aria-label="Refresh" onClick={() => router.reload()}>
                    <RefreshCw className="h-5 w-5" />
                </button>
                <a href="https://support.google.com/google-ads" target="_blank" rel="noreferrer" className="hidden rounded-full p-2 text-muted-foreground hover:bg-muted sm:block" aria-label="Help">
                    <HelpCircle className="h-5 w-5" />
                </a>
                <button className="relative rounded-full p-2 text-muted-foreground hover:bg-muted" aria-label="Notifications">
                    <Bell className="h-5 w-5" />
                    <span className="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-destructive" />
                </button>
                {authenticated && (
                    <button
                        onClick={() => router.post('/logout')}
                        className="ml-1 flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-medium text-primary-foreground"
                        aria-label="Sign out"
                        title="Sign out"
                    >
                        A
                    </button>
                )}
            </div>
        </header>
    );
}

function Sidebar() {
    const isActive = useActiveFeature();

    // On the Overview, intercept the "Overview" link to smooth-scroll to the top
    // (and clear the hash). Tool anchors fall through to native anchor scrolling.
    const handleNav = (e: ReactMouseEvent, f: Feature) => {
        if (f.id === 'overview' && window.location.pathname === '/') {
            e.preventDefault();
            window.history.replaceState(null, '', '/');
            window.dispatchEvent(new HashChangeEvent('hashchange'));
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    return (
        <div className="sticky top-16 hidden h-[calc(100vh-4rem)] shrink-0 lg:flex">
            {/* Icon rail — our real features */}
            <nav className="flex w-[76px] flex-col items-center gap-1 border-r bg-card py-3">
                <a
                    href="/#report"
                    title="Run a report"
                    className="mb-2 flex flex-col items-center gap-1 text-[11px] text-muted-foreground"
                >
                    <span className="flex h-9 w-9 items-center justify-center rounded-full border bg-card shadow-sm">
                        <Plus className="h-5 w-5 text-primary" />
                    </span>
                    Create
                </a>
                {FEATURES.map((f) => {
                    const active = isActive(f);
                    return (
                        <a
                            key={f.id}
                            href={f.href}
                            title={f.desc}
                            onClick={(e) => handleNav(e, f)}
                            className={`flex w-full flex-col items-center gap-1 px-1 py-2 text-[11px] ${active ? 'text-primary' : 'text-muted-foreground hover:text-foreground'}`}
                        >
                            <span className={`flex h-7 w-12 items-center justify-center rounded-full ${active ? 'bg-accent' : ''}`}>
                                <f.icon className="h-5 w-5" />
                            </span>
                            <span className="text-center leading-tight">{f.short}</span>
                        </a>
                    );
                })}
            </nav>

            {/* Nav panel — labelled rows for the same working features */}
            <nav className="w-64 border-r bg-card py-3 pr-2">
                {FEATURES.map((f) => {
                    const active = isActive(f);
                    return (
                        <a
                            key={f.id}
                            href={f.href}
                            onClick={(e) => handleNav(e, f)}
                            className={`mb-0.5 flex items-start gap-3 rounded-r-full py-2 pl-6 pr-3 ${
                                active ? 'bg-accent text-accent-foreground' : 'text-foreground hover:bg-muted'
                            }`}
                        >
                            <f.icon className="mt-0.5 h-5 w-5 shrink-0" />
                            <span className="min-w-0">
                                <span className={`block text-sm ${active ? 'font-medium' : ''}`}>{f.label}</span>
                                <span className="block truncate text-xs text-muted-foreground">{f.desc}</span>
                            </span>
                        </a>
                    );
                })}
            </nav>
        </div>
    );
}

export default function AppLayout({ title, subtitle, authenticated = true, children }: AppLayoutProps) {
    const page = usePage<SharedProps>();
    const flash = page.props.flash ?? {};

    // When arriving from another page at e.g. "/#report", scroll the target tool
    // into view once the Overview has mounted.
    useEffect(() => {
        const id = window.location.hash.replace('#', '');
        if (!id) return;
        const el = document.getElementById(id);
        if (el) setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 120);
    }, []);

    return (
        <div className="min-h-screen bg-background">
            <TopBar authenticated={authenticated} />
            <div className="flex">
                <Sidebar />
                <main className="min-w-0 flex-1">
                    <div className="mx-auto max-w-[1280px] px-4 py-6 sm:px-6">
                        {(title || subtitle) && (
                            <div className="mb-6">
                                {title && <h1 className="text-[1.75rem] font-normal tracking-tight text-foreground">{title}</h1>}
                                {subtitle && <p className="mt-1 text-sm text-muted-foreground">{subtitle}</p>}
                            </div>
                        )}
                        {flash.error && (
                            <Alert variant="destructive" className="mb-5"><AlertDescription>{flash.error}</AlertDescription></Alert>
                        )}
                        {flash.success && (
                            <Alert variant="info" className="mb-5"><AlertDescription>{flash.success}</AlertDescription></Alert>
                        )}
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
