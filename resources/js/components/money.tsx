import { useFormat } from '@/lib/format';
import { cn } from '@/lib/utils';

interface MoneyProps {
    amount: number;
    /** Colour by meaning: income green, expense red, or by the sign of the amount. */
    tone?: 'income' | 'expense' | 'signed' | 'none';
    /** Prefix + or − to show the direction explicitly. */
    showSign?: boolean;
    /** Show the other unit underneath when the user chose «both». */
    withSecondary?: boolean;
    className?: string;
    unitClassName?: string;
}

export function Money({ amount, tone = 'none', showSign = false, withSecondary = false, className, unitClassName }: MoneyProps) {
    const format = useFormat();
    const negative = amount < 0;
    const resolvedTone = tone === 'signed' ? (negative ? 'expense' : amount > 0 ? 'income' : 'none') : tone;
    const sign = showSign ? (negative || tone === 'expense' ? '−' : '+') : negative ? '−' : '';
    const secondary = withSecondary ? format.secondary(Math.abs(amount)) : null;

    return (
        <span
            className={cn(
                'inline-flex flex-col',
                resolvedTone === 'income' && 'text-income',
                resolvedTone === 'expense' && 'text-expense',
                className,
            )}
        >
            <span className="whitespace-nowrap">
                <span className="tabular-nums">
                    {sign}
                    {format.money(Math.abs(amount))}
                </span>
                <span className={cn('ms-1 text-[0.7em] font-normal opacity-70', unitClassName)}>{format.unit}</span>
            </span>
            {secondary && <span className="text-xs font-normal text-muted-foreground">{secondary}</span>}
        </span>
    );
}
