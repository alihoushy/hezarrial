import { Component, type ErrorInfo, type ReactNode } from 'react';
import { TriangleAlertIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { t } from '@/lib/i18n';

interface ErrorBoundaryProps {
    children: ReactNode;
    /** Changing this (e.g. the page URL) clears a previous error, so navigating away recovers. */
    resetKey?: string;
}

interface ErrorBoundaryState {
    failed: boolean;
}

/**
 * Without a boundary, one render error unmounts the whole React tree and the
 * app stays blank, so every later tap seems to do nothing. This keeps the
 * layout (tab bar, language, theme) alive and offers a way out.
 */
export class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
    state: ErrorBoundaryState = { failed: false };

    static getDerivedStateFromError(): ErrorBoundaryState {
        return { failed: true };
    }

    componentDidCatch(error: Error, info: ErrorInfo): void {
        console.error('Page failed to render', error, info.componentStack);
    }

    componentDidUpdate(previous: ErrorBoundaryProps): void {
        if (this.state.failed && previous.resetKey !== this.props.resetKey) {
            this.setState({ failed: false });
        }
    }

    render() {
        if (!this.state.failed) {
            return this.props.children;
        }

        return (
            <div role="alert" className="flex min-h-[60dvh] flex-col items-center justify-center gap-5 px-6 text-center">
                <span className="flex size-16 items-center justify-center rounded-full bg-muted">
                    <TriangleAlertIcon className="size-8 text-muted-foreground" />
                </span>
                <div className="flex flex-col gap-2">
                    <h1 className="text-xl font-extrabold">{t('این صفحه درست نمایش داده نشد')}</h1>
                    <p className="max-w-xs text-sm text-muted-foreground">{t('مشکلی در نمایش این صفحه پیش آمد. دوباره تلاش کنید یا به خانه برگردید.')}</p>
                </div>
                <div className="flex gap-2.5">
                    <Button size="lg" onClick={() => window.location.reload()}>
                        {t('تلاش دوباره')}
                    </Button>
                    <Button size="lg" variant="outline" asChild>
                        <a href={route('dashboard')}>{t('بازگشت به خانه')}</a>
                    </Button>
                </div>
            </div>
        );
    }
}
