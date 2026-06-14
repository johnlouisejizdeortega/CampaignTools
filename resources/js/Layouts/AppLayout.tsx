import { Link, router, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { LogOut } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import ThemeToggle from '@/components/ThemeToggle';
import type { SharedProps } from '@/types';

interface AppLayoutProps {
    title?: string;
    subtitle?: string;
    authenticated?: boolean;
    children: ReactNode;
}

export default function AppLayout({ title, subtitle, authenticated = true, children }: AppLayoutProps) {
    const flash = usePage<SharedProps>().props.flash ?? {};
    return (
        <div className="min-h-screen flex flex-col bg-background">
            <header className="sticky top-0 z-20 border-b bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div className="container flex h-16 items-center justify-between">
                    <Link href="/" className="flex items-center gap-2.5 font-semibold tracking-tight">
                        <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-primary text-[0.65rem] font-bold text-primary-foreground">
                            Ads
                        </span>
                        <span>Google Ads Dashboard</span>
                    </Link>
                    <div className="flex items-center gap-1">
                        <ThemeToggle />
                        {authenticated && (
                            <Button variant="ghost" size="sm" onClick={() => router.post('/logout')}>
                                <LogOut className="mr-1.5 h-4 w-4" />
                                Sign out
                            </Button>
                        )}
                    </div>
                </div>
            </header>

            <main className="container flex-1 py-8">
                {(title || subtitle) && (
                    <div className="mb-7">
                        {title && (
                            <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
                        )}
                        {subtitle && (
                            <p className="mt-1 text-muted-foreground">{subtitle}</p>
                        )}
                    </div>
                )}
                {flash.error && (
                    <Alert variant="destructive" className="mb-5">
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}
                {flash.success && (
                    <Alert variant="info" className="mb-5">
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}
                {children}
            </main>

            <footer className="border-t py-6 text-center text-xs text-muted-foreground">
                Built on the Google Ads API ·{' '}
                <a
                    href="https://groups.google.com/forum/#!forum/adwords-api"
                    target="_blank"
                    rel="noreferrer"
                    className="font-medium underline-offset-4 hover:underline"
                >
                    Support forum
                </a>
            </footer>
        </div>
    );
}
