import type { ReactNode } from 'react';
import { useTheme } from '@/hooks/use-theme';

/** Bare layout for full-screen pages (errors) that still follow the theme. */
export default function PlainLayout({ children }: { children: ReactNode }) {
    useTheme();

    return <>{children}</>;
}
