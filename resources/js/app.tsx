import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import type { ReactNode } from 'react';
import { applyStoredTheme } from '@/lib/theme';

// Apply the saved colour theme as early as possible to avoid a flash.
applyStoredTheme();

const appName = 'Google Ads Dashboard';

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} /> as ReactNode);
    },
    progress: {
        color: '#18181b',
    },
});
