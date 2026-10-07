import { usePage } from '@inertiajs/react';
import { useEffect, useSyncExternalStore } from 'react';
import type { Theme } from '@/types';

const THEME_COLORS = { light: '#f5f6f8', dark: '#0f1012' } as const;

/** Puts the resolved theme on <html> and in the browser-chrome colour (Safari's status bar). */
export function applyTheme(theme: Theme): void {
    const dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

    document.documentElement.classList.toggle('dark', dark);
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? THEME_COLORS.dark : THEME_COLORS.light);
}

/** Keeps the page in sync with the saved theme, and with the OS while it is set to "system". */
export function useTheme(): Theme {
    const theme = usePage().props.settings?.theme ?? 'system';

    useEffect(() => {
        applyTheme(theme);

        if (theme !== 'system') {
            return;
        }

        const query = window.matchMedia('(prefers-color-scheme: dark)');
        const sync = () => applyTheme('system');
        query.addEventListener('change', sync);

        return () => query.removeEventListener('change', sync);
    }, [theme]);

    return theme;
}

/** Whether <html> currently has the dark class; usable outside the Inertia tree (e.g. the toaster). */
export function useIsDark(): boolean {
    return useSyncExternalStore(
        (onChange) => {
            const observer = new MutationObserver(onChange);
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

            return () => observer.disconnect();
        },
        () => document.documentElement.classList.contains('dark'),
        () => false,
    );
}
