import type { LucideIcon } from 'lucide-react';
import { LandmarkIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

const SIZES = {
    sm: { box: 'size-8 rounded-lg', image: 'size-5.5', icon: 'size-4' },
    md: { box: 'size-10 rounded-full', image: 'size-6.5', icon: 'size-[1.15rem]' },
    lg: { box: 'size-12 rounded-2xl', image: 'size-8', icon: 'size-5' },
} as const;

interface BankLogoProps {
    /** Logo slug from the server (`bank`); without one, the fallback icon is shown. */
    bank?: string | null;
    size?: keyof typeof SIZES;
    fallback?: LucideIcon;
    className?: string;
}

/**
 * A bank's logo on a white tile (the artwork is made for white, so it stays readable in dark mode).
 * Logos are plain files, fetched only when a bank is actually shown.
 */
export function BankLogo({ bank, size = 'md', fallback: Fallback = LandmarkIcon, className }: BankLogoProps) {
    const sizes = SIZES[size];

    if (!bank) {
        return (
            <span className={cn('flex shrink-0 items-center justify-center bg-muted text-foreground', sizes.box, className)}>
                <Fallback className={sizes.icon} />
            </span>
        );
    }

    return (
        <span className={cn('flex shrink-0 items-center justify-center bg-white ring-1 ring-black/10', sizes.box, className)}>
            <img src={`/images/banks/${bank}.svg`} alt="" loading="lazy" decoding="async" draggable={false} className={cn('object-contain', sizes.image)} />
        </span>
    );
}
