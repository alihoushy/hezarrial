import type { ReactNode } from 'react';
import { useTheme } from '@/hooks/use-theme';
import { useLocale } from '@/lib/i18n';

/** Centred, tab-bar-free layout for sign-in and first-run setup. */
export default function AuthLayout({ children }: { children: ReactNode }) {
    useLocale();
    useTheme();

    return (
        <div className="pt-safe pb-safe flex min-h-dvh flex-col justify-center px-5 py-10">
            <div className="mx-auto flex w-full max-w-sm flex-col gap-8">
                <div className="flex flex-col items-center gap-3 text-center">
                    <img src="/icons/icon.svg" alt="" width={64} height={64} className="size-16 rounded-2xl shadow-lg ring-1 ring-foreground/5" />
                    <p className="text-xl font-extrabold">هزار ریال</p>
                </div>
                {children}
            </div>
        </div>
    );
}
