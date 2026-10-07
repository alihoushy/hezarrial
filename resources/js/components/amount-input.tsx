import { useLayoutEffect, useRef, type ComponentProps } from 'react';
import { InputGroup, InputGroupAddon, InputGroupButton, InputGroupInput } from '@/components/ui/input-group';
import { toLatinDigits, toPersianDigits, useFormat } from '@/lib/format';
import { cn } from '@/lib/utils';
import { t } from '@/lib/i18n';

interface AmountInputProps extends Omit<ComponentProps<'input'>, 'value' | 'onChange' | 'size'> {
    /** Raw amount in rial as an ASCII string, e.g. "-12500" or "". */
    value: string;
    onValueChange: (value: string) => void;
    allowNegative?: boolean;
    size?: 'default' | 'lg';
}

/** Keeps only digits (and a leading minus when allowed), whatever keyboard typed them. */
function normalize(input: string, allowNegative: boolean): string {
    const latin = toLatinDigits(input);
    const negative = allowNegative && latin.trimStart().startsWith('-');
    const digits = latin.replace(/\D/g, '').replace(/^0+(?=\d)/, '');

    return negative ? `-${digits}` : digits;
}

function group(raw: string, persian: boolean): string {
    if (raw === '' || raw === '-') {
        return raw;
    }

    const negative = raw.startsWith('-');
    const grouped = raw.replace('-', '').replace(/\B(?=(\d{3})+(?!\d))/g, persian ? t('٬') : ',');
    const text = (negative ? '-' : '') + grouped;

    return persian ? toPersianDigits(text) : text;
}

/** Number of significant characters (digits and minus) before a caret position. */
function significantBefore(text: string, caret: number): number {
    return toLatinDigits(text.slice(0, caret)).replace(/[^\d-]/g, '').length;
}

/**
 * Amount field that groups thousands while typing, accepts Persian or Latin
 * digits, keeps the caret where the user is editing, and shows the toman
 * equivalent below. The value it reports is always the raw rial amount.
 */
export function AmountInput({ value, onValueChange, allowNegative = false, size = 'default', className, ...props }: AmountInputProps) {
    const format = useFormat();
    const inputRef = useRef<HTMLInputElement>(null);
    const pendingCaret = useRef<number | null>(null);
    const display = group(value, format.settings.persian_digits);

    useLayoutEffect(() => {
        const input = inputRef.current;

        if (input === null || pendingCaret.current === null || document.activeElement !== input) {
            return;
        }

        let seen = 0;
        let position = 0;

        while (position < display.length && seen < pendingCaret.current) {
            if (/[\d۰-۹-]/.test(display[position])) {
                seen++;
            }
            position++;
        }

        input.setSelectionRange(position, position);
        pendingCaret.current = null;
    }, [display]);

    const negative = value.startsWith('-');
    const absolute = Number(value.replace('-', '') || 0);

    return (
        <div className="flex flex-col gap-1.5">
            <InputGroup className={cn(size === 'lg' && 'h-16 md:h-14', className)}>
                <InputGroupInput
                    ref={inputRef}
                    inputMode="numeric"
                    placeholder={format.digits('0')}
                    autoComplete="off"
                    dir="ltr"
                    value={display}
                    onChange={(event) => {
                        const caret = event.target.selectionStart ?? event.target.value.length;
                        pendingCaret.current = significantBefore(event.target.value, caret);
                        onValueChange(normalize(event.target.value, allowNegative));
                    }}
                    className={cn('text-end tabular-nums', size === 'lg' && 'text-3xl font-bold md:text-2xl')}
                    {...props}
                />
                <InputGroupAddon align="inline-end" className="text-muted-foreground">
                    {t('ریال')}
                </InputGroupAddon>
                {allowNegative && (
                    <InputGroupAddon align="inline-start">
                        <InputGroupButton
                            size="xs"
                            variant={negative ? 'secondary' : 'ghost'}
                            aria-label={t('تغییر علامت')}
                            onClick={() => onValueChange(negative ? value.slice(1) : `-${value}`)}
                        >
                            {negative ? t('منفی') : '±'}
                        </InputGroupButton>
                    </InputGroupAddon>
                )}
            </InputGroup>
            {absolute > 0 && (
                <p className="px-1 text-xs text-muted-foreground">
                    {t(negative ? 'معادل منفی :amount تومان' : 'معادل :amount تومان', { amount: format.number(Math.round(absolute / 10)) })}
                </p>
            )}
        </div>
    );
}
