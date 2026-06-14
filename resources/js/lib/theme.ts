// Lightweight, dependency-free dark-mode handling. The theme is stored in
// localStorage and applied by toggling the `dark` class on <html> (the CSS
// variables in app.css define both palettes).

export function applyStoredTheme(): void {
    try {
        const stored = localStorage.getItem('theme');
        const dark =
            stored === 'dark' ||
            (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
    } catch {
        // Ignore storage/matchMedia access errors (e.g. SSR or privacy mode).
    }
}

export function isDark(): boolean {
    return typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
}

export function toggleTheme(): 'dark' | 'light' {
    const dark = document.documentElement.classList.toggle('dark');
    try {
        localStorage.setItem('theme', dark ? 'dark' : 'light');
    } catch {
        // Ignore storage errors.
    }
    return dark ? 'dark' : 'light';
}
