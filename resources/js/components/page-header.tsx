import { Link } from '@inertiajs/react';
import { ChevronLeftIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { t } from '@/lib/i18n';

interface PageHeaderProps {
    title: string;
    /** Where the back button leads; omit on top-level tabs. */
    back?: string;
    /** Page component of the back target, so going back renders instantly. */
    backComponent?: string;
    actions?: ReactNode;
    className?: string;
}

/** Sticky, translucent top bar in the style of an iOS navigation bar. */
export function PageHeader({ title, back, backComponent, actions, className }: PageHeaderProps) {
    return (
        <header
            className={cn(
                'pt-safe sticky top-0 z-30 border-b border-border/60 bg-background/85 backdrop-blur-xl supports-backdrop-filter:bg-background/70',
                className,
            )}
        >
            <div className="flex h-14 items-center gap-1 px-2">
                {back ? (
                    <Button variant="ghost" size="icon" asChild className="shrink-0 rounded-full">
                        <Link href={back} component={backComponent} aria-label={t('بازگشت')}>
                            <ChevronLeftIcon className="size-6 rtl:rotate-180" />
                        </Link>
                    </Button>
                ) : (
                    <span className="w-2 shrink-0" />
                )}
                <h1 className="min-w-0 flex-1 truncate text-lg font-bold">{title}</h1>
                {actions && <div className="flex shrink-0 items-center gap-1 pe-1">{actions}</div>}
            </div>
        </header>
    );
}

/** Page body with consistent gutters below the header. */
export function PageBody({ className, children }: { className?: string; children: ReactNode }) {
    return <div className={cn('flex flex-col gap-6 px-4 pt-4', className)}>{children}</div>;
}

export function SectionTitle({ children, action }: { children: ReactNode; action?: ReactNode }) {
    return (
        <div className="flex items-center justify-between px-1 pb-2">
            <h2 className="text-sm font-semibold text-muted-foreground">{children}</h2>
            {action}
        </div>
    );
}
