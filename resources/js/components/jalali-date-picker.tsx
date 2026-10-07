import { CalendarIcon, ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { ResponsiveModal } from '@/components/responsive-modal';
import { Button } from '@/components/ui/button';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { todayIso, useFormat } from '@/lib/format';
import { JALALI_MONTHS, JALALI_WEEKDAYS_SHORT, daysInJalaliMonth, firstWeekdayOfJalaliMonth, isoToJalali, jalaliToIso, shiftJalaliMonth } from '@/lib/jalali';
import { cn } from '@/lib/utils';
import { t } from '@/lib/i18n';

interface JalaliDatePickerProps {
    id?: string;
    /** ISO (Gregorian) date, or "" when empty. The server always receives this form. */
    value: string;
    onChange: (value: string) => void;
    /** Adds a "remove date" action, for optional fields. */
    clearable?: boolean;
    title?: string;
    'aria-invalid'?: boolean;
}

/**
 * A Persian (Solar Hijri) calendar in a bottom sheet. The field shows the Jalali
 * date, but the value it reports stays an ISO date, so nothing changes server-side.
 */
export function JalaliDatePicker({ id, value, onChange, clearable = false, title = t('انتخاب تاریخ'), ...props }: JalaliDatePickerProps) {
    const format = useFormat();
    const [open, setOpen] = useState(false);
    const today = useMemo(() => isoToJalali(todayIso())!, []);
    const selected = isoToJalali(value);
    const [view, setView] = useState(() => selected ?? today);

    // Reopening always starts on the month of the current value.
    useEffect(() => {
        if (open) {
            setView(isoToJalali(value) ?? today);
        }
    }, [open, value, today]);

    const days = daysInJalaliMonth(view.year, view.month);
    const offset = firstWeekdayOfJalaliMonth(view.year, view.month);
    const years = Array.from({ length: 21 }, (_, index) => today.year - 10 + index);
    const yearOptions = years.includes(view.year) ? years : [view.year, ...years].sort((a, b) => a - b);

    const move = (delta: number) => setView((current) => ({ ...current, ...shiftJalaliMonth(current, delta), day: 1 }));
    const pick = (day: number) => {
        onChange(jalaliToIso({ year: view.year, month: view.month, day }));
        setOpen(false);
    };

    return (
        <>
            <button
                type="button"
                id={id}
                onClick={() => setOpen(true)}
                aria-haspopup="dialog"
                aria-invalid={props['aria-invalid']}
                className={cn(
                    'flex h-11 w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-2.5 text-start text-base shadow-xs transition-[color,box-shadow] outline-none md:h-9 md:text-sm dark:bg-input/30',
                    'focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 aria-invalid:border-destructive',
                    !value && 'text-muted-foreground',
                )}
            >
                <span className="truncate">{value ? format.date(value) : title}</span>
                <CalendarIcon className="size-4 shrink-0 text-muted-foreground" />
            </button>
            {value && <p className="px-1 text-xs text-muted-foreground">{format.longDate(value)}</p>}

            <ResponsiveModal open={open} onOpenChange={setOpen} title={title}>
                <div className="flex flex-col gap-4 pb-4">
                    <div className="flex items-center gap-2">
                        <Button type="button" variant="outline" size="icon" className="shrink-0 rounded-full" onClick={() => move(-1)} aria-label={t('ماه قبل')}>
                            <ChevronRightIcon />
                        </Button>
                        <NativeSelect
                            className="min-w-0 flex-1"
                            value={view.month}
                            aria-label={t('ماه')}
                            onChange={(event) => setView((current) => ({ ...current, month: Number(event.target.value), day: 1 }))}
                        >
                            {JALALI_MONTHS.map((name, index) => (
                                <NativeSelectOption key={name} value={index + 1}>
                                    {name}
                                </NativeSelectOption>
                            ))}
                        </NativeSelect>
                        <NativeSelect className="w-28 shrink-0" value={view.year} aria-label={t('سال')} onChange={(event) => setView((current) => ({ ...current, year: Number(event.target.value), day: 1 }))}>
                            {yearOptions.map((year) => (
                                <NativeSelectOption key={year} value={year}>
                                    {format.digits(String(year))}
                                </NativeSelectOption>
                            ))}
                        </NativeSelect>
                        <Button type="button" variant="outline" size="icon" className="shrink-0 rounded-full" onClick={() => move(1)} aria-label={t('ماه بعد')}>
                            <ChevronLeftIcon />
                        </Button>
                    </div>

                    <div role="grid" aria-label={`${JALALI_MONTHS[view.month - 1]} ${view.year}`} className="grid grid-cols-7 gap-y-1">
                        {JALALI_WEEKDAYS_SHORT.map((weekday, index) => (
                            <span key={weekday} className={cn('pb-1 text-center text-xs font-medium text-muted-foreground', index === 6 && 'text-expense')}>
                                {weekday}
                            </span>
                        ))}
                        {Array.from({ length: offset }, (_, index) => (
                            <span key={`blank-${index}`} />
                        ))}
                        {Array.from({ length: days }, (_, index) => {
                            const day = index + 1;
                            const isSelected = selected?.year === view.year && selected.month === view.month && selected.day === day;
                            const isToday = today.year === view.year && today.month === view.month && today.day === day;
                            const isFriday = (offset + index) % 7 === 6;

                            return (
                                <button
                                    key={day}
                                    type="button"
                                    role="gridcell"
                                    aria-selected={isSelected}
                                    aria-current={isToday ? 'date' : undefined}
                                    onClick={() => pick(day)}
                                    className={cn(
                                        'mx-auto flex size-11 items-center justify-center rounded-full text-[0.95rem] tabular-nums transition-colors select-none active:scale-95',
                                        isFriday && 'text-expense',
                                        isToday && !isSelected && 'font-bold ring-1 ring-foreground/40',
                                        isSelected ? 'bg-primary font-bold text-primary-foreground' : 'hover:bg-muted',
                                    )}
                                >
                                    {format.number(day)}
                                </button>
                            );
                        })}
                    </div>

                    <div className="grid grid-cols-2 gap-2.5">
                        <Button
                            type="button"
                            variant="outline"
                            size="lg"
                            onClick={() => {
                                onChange(todayIso());
                                setOpen(false);
                            }}
                        >
                            {t('امروز')}
                        </Button>
                        {clearable && value ? (
                            <Button
                                type="button"
                                variant="outline"
                                size="lg"
                                onClick={() => {
                                    onChange('');
                                    setOpen(false);
                                }}
                            >
                                {t('حذف تاریخ')}
                            </Button>
                        ) : (
                            <Button type="button" variant="ghost" size="lg" onClick={() => setOpen(false)}>
                                {t('انصراف')}
                            </Button>
                        )}
                    </div>
                </div>
            </ResponsiveModal>
        </>
    );
}
