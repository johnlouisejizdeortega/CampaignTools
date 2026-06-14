import { Link, router, usePage } from '@inertiajs/react';
import type { ComponentType, ReactNode } from 'react';
import {
    Bell, ChevronDown, CreditCard, HelpCircle, LayoutGrid, Lightbulb,
    Megaphone, Menu, Plus, RefreshCw, Search, Settings, Target, Trophy, Wrench,
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

interface RailItem { icon: ComponentType<{ className?: string }>; label: string; href?: string; active?: boolean }

// Far-left icon rail — mirrors the Google Ads console sections. Items without an
// href are structural (the app doesn't implement that section yet).
const RAIL: RailItem[] = [
    { icon: Target, label: 'Campaigns', href: '/', active: true },
    { icon: Trophy, label: 'Goals' },
    { icon: Wrench, label: 'Tools' },
    { icon: CreditCard, label: 'Billing' },
    { icon: Settings, label: 'Admin' },
];

// Second-column nav panel — maps to the features this app actually supports.
const NAV: { icon: ComponentType<{ className?: string }>; label: string; href: string }[] = [
    { icon: LayoutGrid, label: 'Overview', href: '/' },
    { icon: Lightbulb, label: 'Recommendations', href: '/#optimization' },
    { icon: Megaphone, label: 'Campaigns', href: '/#campaigns' },
];

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
            <button className="ml-1 hidden items-center gap-1 rounded-full px-2 py-1 text-sm text-muted-foreground hover:bg-muted md:flex">
                <span className="font-medium text-foreground">Account</span>
                <ChevronDown className="h-4 w-4" />
            </button>

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
                <button className="hidden rounded-full p-2 text-muted-foreground hover:bg-muted sm:block" aria-label="Refresh">
                    <RefreshCw className="h-5 w-5" />
                </button>
                <button className="hidden rounded-full p-2 text-muted-foreground hover:bg-muted sm:block" aria-label="Help">
                    <HelpCircle className="h-5 w-5" />
                </button>
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

function Sidebar({ currentUrl }: { currentUrl: string }) {
    return (
        <div className="sticky top-16 hidden h-[calc(100vh-4rem)] shrink-0 lg:flex">
            {/* Icon rail */}
            <nav className="flex w-[72px] flex-col items-center gap-1 border-r bg-card py-3">
                <button className="mb-2 flex flex-col items-center gap-1 text-[11px] text-muted-foreground">
                    <span className="flex h-9 w-9 items-center justify-center rounded-full border bg-card shadow-sm">
                        <Plus className="h-5 w-5 text-primary" />
                    </span>
                    Create
                </button>
                {RAIL.map(({ icon: Icon, label, href, active }) => {
                    const cls = `flex w-full flex-col items-center gap-1 px-1 py-2 text-[11px] ${active ? 'text-primary' : 'text-muted-foreground'}`;
                    const inner = (
                        <>
                            <span className={`flex h-7 w-7 items-center justify-center rounded-lg ${active ? 'bg-accent' : ''}`}>
                                <Icon className="h-5 w-5" />
                            </span>
                            <span className="text-center leading-tight">{label}</span>
                        </>
                    );
                    return href
                        ? <Link key={label} href={href} className={cls}>{inner}</Link>
                        : <span key={label} className={`${cls} cursor-default`}>{inner}</span>;
                })}
            </nav>

            {/* Nav panel */}
            <nav className="w-60 border-r bg-card py-3 pr-2">
                {NAV.map(({ icon: Icon, label, href }) => {
                    const active = href === '/' ? currentUrl === '/' : currentUrl.startsWith(href.replace('/#', '/'));
                    return (
                        <Link
                            key={label}
                            href={href}
                            className={`mb-0.5 flex items-center gap-3 rounded-r-full py-2 pl-6 pr-3 text-sm ${
                                active ? 'bg-accent font-medium text-accent-foreground' : 'text-foreground hover:bg-muted'
                            }`}
                        >
                            <Icon className="h-5 w-5 shrink-0" />
                            {label}
                        </Link>
                    );
                })}
            </nav>
        </div>
    );
}

export default function AppLayout({ title, subtitle, authenticated = true, children }: AppLayoutProps) {
    const page = usePage<SharedProps>();
    const flash = page.props.flash ?? {};
    const currentUrl = page.url ?? '/';

    return (
        <div className="min-h-screen bg-background">
            <TopBar authenticated={authenticated} />
            <div className="flex">
                <Sidebar currentUrl={currentUrl} />
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
