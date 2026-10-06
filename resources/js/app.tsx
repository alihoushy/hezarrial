import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { toast } from 'sonner';
import { DirectionProvider } from '@/components/ui/direction';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';

const APP_NAME = 'هزار ریال';

// Controllers flash `status` messages; show each once as a toast.
router.on('flash', (event) => {
    const status = event.detail.flash.status;

    if (status) {
        toast.success(status);
    }
});

router.on('networkError', () => {
    toast.error('ارتباط با سرور برقرار نشد. اتصال اینترنت را بررسی کنید.');
});

// Pages are code-split, so the first tap on a tab would wait for its chunk on a slow
// connection. Fetch the tab pages once the browser is idle (Vite dedupes the chunks).
const tabPages = import.meta.glob(['./pages/dashboard.tsx', './pages/transactions/index.tsx', './pages/accounts/index.tsx', './pages/settings/index.tsx', './pages/transactions/form.tsx']);
const warmUp = () => Object.values(tabPages).forEach((load) => void load());

if ('requestIdleCallback' in window) {
    window.requestIdleCallback(warmUp, { timeout: 4000 });
} else {
    setTimeout(warmUp, 2000);
}

createInertiaApp({
    title: (title) => (title ? `${title} · ${APP_NAME}` : APP_NAME),
    pages: './pages',
    layout: (name) => (name === 'error' ? null : name.startsWith('auth/') ? AuthLayout : AppLayout),
    progress: {
        color: 'var(--color-brand)',
        delay: 250,
        showSpinner: false,
    },
    withApp: (app) => (
        <DirectionProvider dir="rtl">
            {app}
            <Toaster
                dir="rtl"
                position="top-center"
                offset={{ top: 'calc(env(safe-area-inset-top) + 12px)' }}
                mobileOffset={{ top: 'calc(env(safe-area-inset-top) + 12px)' }}
                toastOptions={{ classNames: { toast: 'cn-toast font-sans' } }}
            />
        </DirectionProvider>
    ),
});
