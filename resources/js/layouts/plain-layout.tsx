import type { ReactNode } from 'react';
import { useTheme } from '@/hooks/use-theme';
import { useLocale } from '@/lib/i18n';

/** Bare layout for full-screen pages (errors) that still follow the theme. */
export default function PlainLayout({ children }: { children: ReactNode }) {
    useLocale();
    useTheme();

    return <>{children}</>;
}
