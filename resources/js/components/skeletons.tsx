import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

/** Placeholder rows shaped like the real list rows (icon, two lines, amount). */
export function ListSkeleton({ rows = 6, className }: { rows?: number; className?: string }) {
    return (
        <div className={cn('overflow-hidden rounded-2xl bg-card ring-1 ring-foreground/5', className)} aria-busy="true" aria-label="در حال بارگذاری">
            {Array.from({ length: rows }, (_, index) => (
                <div key={index} className="flex items-center gap-3 border-b border-border/60 px-4 py-3.5 last:border-b-0">
                    <Skeleton className="size-10 shrink-0 rounded-full" />
                    <div className="flex flex-1 flex-col gap-2">
                        <Skeleton className="h-3.5 w-2/5" />
                        <Skeleton className="h-3 w-1/4" />
                    </div>
                    <Skeleton className="h-4 w-20" />
                </div>
            ))}
        </div>
    );
}

export function HeroSkeleton({ className }: { className?: string }) {
    return (
        <div className={cn('flex flex-col gap-4 rounded-3xl bg-card p-5 ring-1 ring-foreground/5', className)} aria-busy="true">
            <Skeleton className="h-3.5 w-24" />
            <Skeleton className="h-9 w-48" />
            <div className="grid grid-cols-2 gap-3 pt-1">
                <Skeleton className="h-14 rounded-xl" />
                <Skeleton className="h-14 rounded-xl" />
            </div>
        </div>
    );
}

export function CardsRowSkeleton({ count = 2 }: { count?: number }) {
    return (
        <div className="flex gap-3 overflow-hidden" aria-busy="true">
            {Array.from({ length: count }, (_, index) => (
                <div key={index} className="flex w-[68%] shrink-0 flex-col gap-4 rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
                    <div className="flex items-center gap-2">
                        <Skeleton className="size-8 rounded-full" />
                        <Skeleton className="h-3.5 w-24" />
                    </div>
                    <Skeleton className="h-6 w-32" />
                </div>
            ))}
        </div>
    );
}

export function StatsSkeleton({ count = 3 }: { count?: number }) {
    return (
        <div className="grid gap-3" style={{ gridTemplateColumns: `repeat(${count}, minmax(0, 1fr))` }} aria-busy="true">
            {Array.from({ length: count }, (_, index) => (
                <div key={index} className="flex flex-col gap-2.5 rounded-2xl bg-card p-3.5 ring-1 ring-foreground/5">
                    <Skeleton className="h-3 w-12" />
                    <Skeleton className="h-5 w-16" />
                </div>
            ))}
        </div>
    );
}

export function ChartSkeleton({ className }: { className?: string }) {
    return (
        <div className={cn('flex flex-col gap-4 rounded-2xl bg-card p-4 ring-1 ring-foreground/5', className)} aria-busy="true">
            <Skeleton className="h-4 w-28" />
            <div className="flex h-44 items-end gap-2">
                {[40, 65, 30, 80, 55, 70, 45, 90, 60, 35, 75, 50].map((height, index) => (
                    <Skeleton key={index} className="flex-1 rounded-t-md rounded-b-none" style={{ height: `${height}%` }} />
                ))}
            </div>
        </div>
    );
}

export function FormSkeleton({ fields = 5 }: { fields?: number }) {
    return (
        <div className="flex flex-col gap-5" aria-busy="true" aria-label="در حال بارگذاری فرم">
            {Array.from({ length: fields }, (_, index) => (
                <div key={index} className="flex flex-col gap-2">
                    <Skeleton className="h-3.5 w-20" />
                    <Skeleton className="h-11 w-full rounded-md" />
                </div>
            ))}
            <Skeleton className="mt-2 h-11 w-full rounded-md" />
        </div>
    );
}

export function DetailSkeleton() {
    return (
        <div className="flex flex-col gap-6" aria-busy="true">
            <div className="flex flex-col items-center gap-3 rounded-3xl bg-card p-6 ring-1 ring-foreground/5">
                <Skeleton className="size-14 rounded-full" />
                <Skeleton className="h-4 w-32" />
                <Skeleton className="h-9 w-44" />
            </div>
            <ListSkeleton rows={4} />
        </div>
    );
}
