import { isLeapJalaaliYear, jalaaliMonthLength, toGregorian, toJalaali } from 'jalaali-js';

export interface JalaliDate {
    year: number;
    month: number;
    day: number;
}

export const JALALI_MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'] as const;

/** Saturday-first, the way the Persian calendar week is laid out. */
export const JALALI_WEEKDAYS_SHORT = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'] as const;

const pad = (value: number) => String(value).padStart(2, '0');

/** "2026-10-07" -> { year: 1405, month: 7, day: 15 }; null for anything that is not a valid date. */
export function isoToJalali(iso: string | null | undefined): JalaliDate | null {
    const match = iso?.match(/^(\d{4})-(\d{2})-(\d{2})/);

    if (!match) {
        return null;
    }

    const { jy, jm, jd } = toJalaali(Number(match[1]), Number(match[2]), Number(match[3]));

    return { year: jy, month: jm, day: jd };
}

/** The ISO (Gregorian) string the server stores, from a Jalali date. */
export function jalaliToIso({ year, month, day }: JalaliDate): string {
    const { gy, gm, gd } = toGregorian(year, month, day);

    return `${gy}-${pad(gm)}-${pad(gd)}`;
}

export function daysInJalaliMonth(year: number, month: number): number {
    return jalaaliMonthLength(year, month);
}

export function isLeapJalaliYear(year: number): boolean {
    return isLeapJalaaliYear(year);
}

/** Column of the month's first day in a Saturday-first week (0 = Saturday). */
export function firstWeekdayOfJalaliMonth(year: number, month: number): number {
    const { gy, gm, gd } = toGregorian(year, month, 1);
    const sundayFirst = new Date(gy, gm - 1, gd).getDay();

    return (sundayFirst + 1) % 7;
}

/** Month arithmetic that rolls the year over in both directions. */
export function shiftJalaliMonth({ year, month }: Pick<JalaliDate, 'year' | 'month'>, delta: number): Pick<JalaliDate, 'year' | 'month'> {
    const index = year * 12 + (month - 1) + delta;

    return { year: Math.floor(index / 12), month: (index % 12) + 1 };
}
