import type { ReactNode } from 'react';
import { DirectionProvider } from '@/components/ui/direction';
import { Toaster } from '@/components/ui/sonner';
import { useDocumentDirection } from '@/lib/i18n';

/** App-wide providers that follow the document's direction, which changes with the language. */
export function Providers({ children }: { children: ReactNode }) {
    const dir = useDocumentDirection();

    return (
        <DirectionProvider dir={dir}>
            {children}
            <Toaster
                dir={dir}
                position="top-center"
                offset={{ top: 'calc(env(safe-area-inset-top) + 12px)' }}
                mobileOffset={{ top: 'calc(env(safe-area-inset-top) + 12px)' }}
                toastOptions={{ classNames: { toast: 'cn-toast font-sans' } }}
            />
        </DirectionProvider>
    );
}
