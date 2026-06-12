import { Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function AppLayout({ title, subtitle, authenticated = true, children }) {
    return (
        <div className="min-h-screen flex flex-col bg-background">
            <header className="sticky top-0 z-20 border-b bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div className="container flex h-16 items-center justify-between">
                    <Link href="/" className="flex items-center gap-2 font-semibold">
                        <span className="flex h-7 w-7 items-center justify-center rounded-md bg-primary text-xs font-bold text-primary-foreground">
                            Ads
                        </span>
                        <span>Google Ads Dashboard</span>
                    </Link>
                    {authenticated && (
                        <Button variant="outline" size="sm" onClick={() => router.post('/logout')}>
                            Sign out
                        </Button>
                    )}
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
