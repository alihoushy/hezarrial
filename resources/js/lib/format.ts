import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { CurrencyDisplay, Settings } from '@/types';

const PERSIAN_DIGITS = '۰۱۲۳۴۵۶۷۸۹';

export function toPersianDigits(value: string): string {
    return value.replace(/[0-9]/g, (digit) => PERSIAN_DIGITS[Number(digit)]);
}

/** Converts Persian and Arabic-Indic digits (as typed on an iOS Persian keyboard) to ASCII. */
export function toLatinDigits(value: string): string {
    return value
        .replace(/[۰-۹]/g, (digit) => String(digit.charCodeAt(0) - 0x06f0))
        .replace(/[٠-٩]/g, (digit) => String(digit.charCodeAt(0) - 0x0660));
}

/** Parses a Y-m-d string as a local calendar date, never shifting it through UTC. */
export function parseDate(value: string | null | undefined): Date | null {
    if (!value) {
        return null;
    }

    const [year, month, day] = value.slice(0, 10).split('-').map(Number);

    if (!year || !month || !day) {
        const parsed = new Date(value);

        return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

    return new Date(year, month - 1, day);
}

export function todayIso(): string {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

function sameDay(a: Date, b: Date): boolean {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

export interface Formatters {
    settings: Settings;
    /** Plain number in the user's digits, e.g. ۱۲٬۵۰۰. */
    number: (value: number, fractionDigits?: number) => string;
    /** Amount converted to the preferred unit, without the unit label. */
    money: (rial: number) => string;
    /** The preferred unit label: ریال or تومان. */
    unit: string;
    /** The other unit, shown as a secondary line when the user chose both. */
    secondary: (rial: number) => string | null;
    /** Converts any digits in a string to the user's preference. */
    digits: (value: string) => string;
    /** ۱۴ مهر ۱۴۰۵ */
    date: (value: string | null | undefined) => string;
    /** ۱۴ مهر */
    shortDate: (value: string | null | undefined) => string;
    /** امروز / دیروز / فردا, otherwise دوشنبه ۱۴ مهر */
    relativeDay: (value: string | null | undefined) => string;
    /** دوشنبه ۱۴ مهر ۱۴۰۵ */
    longDate: (value: string | null | undefined) => string;
    /** Day of month only, for chart axes. */
    dayOfMonth: (value: string | null | undefined) => string;
    dateTime: (value: string | null | undefined) => string;
    fileSize: (bytes: number) => string;
}

export function createFormatters(settings: Settings): Formatters {
    const persian = settings.persian_digits;
    // fa-IR uses the Persian (Solar Hijri) calendar; -nu-latn keeps ASCII digits.
    const locale = persian ? 'fa-IR' : 'fa-IR-u-nu-latn';
    const display: CurrencyDisplay = settings.currency_display;
    const toman = display === 'toman';

    const numberFormat = (fractionDigits: number) =>
        new Intl.NumberFormat(persian ? 'fa-IR' : 'en-US', { maximumFractionDigits: fractionDigits });
    const integer = numberFormat(0);

    const dateFormat = new Intl.DateTimeFormat(locale, { year: 'numeric', month: 'long', day: 'numeric' });
    const shortFormat = new Intl.DateTimeFormat(locale, { month: 'long', day: 'numeric' });
    const weekdayShort = new Intl.DateTimeFormat(locale, { weekday: 'long', month: 'long', day: 'numeric' });
    // ICU's Persian pattern for weekday + full date is "year month day, weekday", so join the parts ourselves.
    const weekdayOnly = new Intl.DateTimeFormat(locale, { weekday: 'long' });
    const dayFormat = new Intl.DateTimeFormat(locale, { day: 'numeric' });
    const dateTimeFormat = new Intl.DateTimeFormat(locale, { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });

    const withDate = (fn: (date: Date) => string) => (value: string | null | undefined) => {
        const date = parseDate(value);

        return date ? fn(date) : '—';
    };

    return {
        settings,
        number: (value, fractionDigits = 0) => numberFormat(fractionDigits).format(value),
        money: (rial) => integer.format(toman ? Math.round(rial / 10) : rial),
        unit: toman ? 'تومان' : 'ریال',
        secondary: (rial) => (display === 'both' ? `${integer.format(Math.round(rial / 10))} تومان` : null),
        digits: (value) => (persian ? toPersianDigits(value) : value),
        date: withDate((date) => dateFormat.format(date)),
        shortDate: withDate((date) => shortFormat.format(date)),
        relativeDay: withDate((date) => {
            const today = new Date();
            const offset = (days: number) => new Date(today.getFullYear(), today.getMonth(), today.getDate() + days);

            if (sameDay(date, today)) return 'امروز';
            if (sameDay(date, offset(-1))) return 'دیروز';
            if (sameDay(date, offset(1))) return 'فردا';

            return weekdayShort.format(date);
        }),
        longDate: withDate((date) => `${weekdayOnly.format(date)} ${dateFormat.format(date)}`),
        dayOfMonth: withDate((date) => dayFormat.format(date)),
        dateTime: (value) => {
            if (!value) return '—';
            const date = new Date(value);

            return Number.isNaN(date.getTime()) ? '—' : dateTimeFormat.format(date);
        },
        fileSize: (bytes) => {
            if (bytes < 1024) return `${integer.format(bytes)} بایت`;
            if (bytes < 1024 * 1024) return `${numberFormat(1).format(bytes / 1024)} کیلوبایت`;

            return `${numberFormat(1).format(bytes / 1024 / 1024)} مگابایت`;
        },
    };
}

/** Formatters bound to the signed-in user's display settings (available on every page). */
export function useFormat(): Formatters {
    const settings = usePage().props.settings;
    const persian = settings?.persian_digits ?? true;
    const display = settings?.currency_display ?? 'both';

    return useMemo(() => createFormatters({ persian_digits: persian, currency_display: display }), [persian, display]);
}
