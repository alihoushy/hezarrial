import { CheckIcon, ChevronDownIcon, SearchIcon } from 'lucide-react';
import { useMemo, useState } from 'react';
import { BankLogo } from '@/components/bank-logo';
import { ResponsiveModal } from '@/components/responsive-modal';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';
import { normalizeSearch } from '@/lib/search';
import { cn } from '@/lib/utils';
import type { BankOption } from '@/types';

interface BankFieldProps {
    id?: string;
    banks?: BankOption[];
    /** The chosen bank's slug, or '' for none. */
    value: string;
    onChange: (bank: string) => void;
    /** Shown until `banks` has loaded (instant visits render before the server answers). */
    fallbackLabel?: string | null;
    otherName: string;
    onOtherNameChange: (name: string) => void;
    'aria-invalid'?: boolean;
}

/**
 * Picks a bank from a searchable grid of logos. For a bank that is not in the list the user
 * chooses "other" and types its name.
 */
export function BankField({ id, banks = [], value, onChange, fallbackLabel, otherName, onOtherNameChange, ...props }: BankFieldProps) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const selected = banks.find((bank) => bank.value === value);
    const label = selected?.label ?? (value ? fallbackLabel : null);

    const visible = useMemo(() => {
        const needle = normalizeSearch(query);

        return needle ? banks.filter((bank) => normalizeSearch(bank.label).includes(needle) || bank.value.replace(/-/g, ' ').includes(needle)) : banks;
    }, [banks, query]);

    const choose = (bank: string) => {
        onChange(bank);
        setOpen(false);
        setQuery('');
    };

    return (
        <div className="flex flex-col gap-3">
            <button
                id={id}
                type="button"
                onClick={() => setOpen(true)}
                aria-haspopup="dialog"
                aria-invalid={props['aria-invalid']}
                className="flex h-12 w-full items-center gap-3 rounded-xl border border-input bg-card px-3 text-start text-base transition-colors active:bg-muted/70 aria-invalid:border-destructive md:text-sm"
            >
                <BankLogo bank={value || null} size="sm" />
                <span className={cn('flex-1 truncate', !label && 'text-muted-foreground')}>{label ?? t('انتخاب بانک')}</span>
                <ChevronDownIcon className="size-4 text-muted-foreground" />
            </button>

            {!value && (
                <Input
                    value={otherName}
                    onChange={(event) => onOtherNameChange(event.target.value)}
                    placeholder={t('اگر بانک در فهرست نیست، نامش را بنویسید')}
                    aria-label={t('نام بانک')}
                />
            )}

            <ResponsiveModal open={open} onOpenChange={setOpen} title={t('انتخاب بانک')}>
                <div className="flex flex-col gap-4 pb-4">
                    <div className="relative">
                        <SearchIcon className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder={t('جست‌وجوی بانک')} className="ps-9" aria-label={t('جست‌وجوی بانک')} />
                    </div>

                    {visible.length === 0 ? (
                        <p className="py-6 text-center text-sm text-muted-foreground">{t('بانکی پیدا نشد. می‌توانید «سایر» را بزنید و نام را بنویسید.')}</p>
                    ) : (
                        <div className="grid grid-cols-3 gap-2.5">
                            {visible.map((bank) => (
                                <button
                                    key={bank.value}
                                    type="button"
                                    onClick={() => choose(bank.value)}
                                    aria-pressed={bank.value === value}
                                    className={cn(
                                        'relative flex min-h-24 flex-col items-center gap-2 rounded-2xl border border-border bg-card px-2 py-3 text-center text-xs leading-5 font-medium transition-colors active:bg-muted/70',
                                        bank.value === value && 'border-primary bg-primary/5',
                                    )}
                                >
                                    {bank.value === value && <CheckIcon className="absolute end-2 top-2 size-4 text-primary" />}
                                    <BankLogo bank={bank.value} size="lg" />
                                    <span className="line-clamp-2">{bank.label}</span>
                                </button>
                            ))}
                        </div>
                    )}

                    <button type="button" onClick={() => choose('')} className="h-11 rounded-xl border border-dashed border-border text-sm text-muted-foreground active:bg-muted/70">
                        {t('سایر / بدون بانک')}
                    </button>
                </div>
            </ResponsiveModal>
        </div>
    );
}
