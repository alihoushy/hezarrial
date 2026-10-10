import type { ReactNode } from 'react';

/** The card every sign-in, sign-up and recovery page sits in. */
export function AuthCard({ title, description, children, footer }: { title: string; description?: ReactNode; children: ReactNode; footer?: ReactNode }) {
    return (
        <div className="flex flex-col gap-6 rounded-3xl bg-card p-6 ring-1 ring-foreground/5">
            <div className="flex flex-col gap-1">
                <h1 className="text-xl font-extrabold">{title}</h1>
                {description && <p className="text-sm leading-6 text-muted-foreground">{description}</p>}
            </div>
            {children}
            {footer && <div className="border-t border-border/60 pt-4 text-center text-sm text-muted-foreground">{footer}</div>}
        </div>
    );
}
