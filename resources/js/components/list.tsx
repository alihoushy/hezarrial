import { Link } from '@inertiajs/react';
import { ChevronLeftIcon, type LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import type { Tone } from '@/lib/labels';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/** An inset, grouped list card in the style of iOS settings. */
export function ListCard({ className, children }: { className?: string; children: ReactNode }) {
    return <div className={cn('overflow-hidden rounded-2xl bg-card ring-1 ring-foreground/5', className)}>{children}</div>;
}

interface ListRowProps {
    icon?: LucideIcon;
    /** Tailwind classes for the icon bubble, e.g. "bg-income/10 text-income". */
    iconClassName?: string;
    media?: ReactNode;
    title: ReactNode;
    subtitle?: ReactNode;
    trailing?: ReactNode;
    href?: string;
    /** Page component of the target, so the visit renders instantly with a skeleton. */
    component?: string;
    pageProps?: Record<string, unknown>;
    chevron?: boolean;
    onClick?: () => void;
    className?: string;
}

export function ListRow({ icon: Icon, iconClassName, media, title, subtitle, trailing, href, component, pageProps, chevron, onClick, className }: ListRowProps) {
    const content = (
        <>
            {media ??
                (Icon && (
                    <span className={cn('flex size-10 shrink-0 items-center justify-center rounded-full bg-muted text-foreground', iconClassName)}>
                        <Icon className="size-[1.15rem]" />
                    </span>
                ))}
            <span className="flex min-w-0 flex-1 flex-col gap-0.5">
                <span className="truncate text-[0.95rem] font-medium">{title}</span>
                {subtitle && <span className="truncate text-xs text-muted-foreground">{subtitle}</span>}
            </span>
            {trailing && <span className="flex shrink-0 flex-col items-end text-end">{trailing}</span>}
            {chevron && <ChevronLeftIcon className="size-4 shrink-0 text-muted-foreground/60" />}
        </>
    );

    const classes = cn(
        'flex min-h-16 w-full items-center gap-3 border-b border-border/60 px-4 py-3 text-start last:border-b-0',
        (href || onClick) && 'transition-colors active:bg-muted/70 select-none',
        className,
    );

    if (href) {
        return (
            <Link href={href} component={component} pageProps={pageProps} className={classes}>
                {content}
            </Link>
        );
    }

    if (onClick) {
        return (
            <button type="button" onClick={onClick} className={classes}>
                {content}
            </button>
        );
    }

    return <div className={classes}>{content}</div>;
}

const toneClasses: Record<Tone, string> = {
    default: 'bg-secondary text-secondary-foreground',
    success: 'bg-income/12 text-income',
    warning: 'bg-warning/15 text-warning',
    danger: 'bg-expense/12 text-expense',
    muted: 'bg-muted text-muted-foreground',
};

export function StatusBadge({ label, tone }: { label: string; tone: Tone }) {
    return (
        <Badge variant="secondary" className={cn('font-medium', toneClasses[tone])}>
            {t(label)}
        </Badge>
    );
}
