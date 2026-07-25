import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import type { ReactNode } from 'react';
import { applyStoredTheme } from '@/lib/theme';
import ErrorBoundary from '@/components/ErrorBoundary';

// Apply the saved colour theme as early as possible to avoid a flash.
applyStoredTheme();

// After a new deploy, a browser may still hold hashed chunks from the previous
// build. When a code-split page chunk fails to load, Vite fires this event —
// reload once (guarded against a loop) to pull the fresh build instead of
// showing a blank screen. This is the usual cause of "some pages went white
// after deploying" while newly-added pages (uncached) still work.
window.addEventListener('vite:preloadError', () => {
    const KEY = 'chunk-reloaded-at';
    const last = Number(sessionStorage.getItem(KEY) || 0);
    if (Date.now() - last > 10000) {
        sessionStorage.setItem(KEY, String(Date.now()));
        window.location.reload();
    }
});

const appName = 'Google Ads Dashboard';

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        createRoot(el).render(
            <ErrorBoundary>
                <App {...props} />
            </ErrorBoundary> as ReactNode,
        );
    },
    progress: {
        color: '#18181b',
    },
});
