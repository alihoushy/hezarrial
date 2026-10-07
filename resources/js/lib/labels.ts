import {
    ArrowDownToLineIcon,
    ArrowLeftRightIcon,
    ArrowUpFromLineIcon,
    BanknoteIcon,
    CircleDollarSignIcon,
    CreditCardIcon,
    HandCoinsIcon,
    LandmarkIcon,
    ReceiptTextIcon,
    SlidersHorizontalIcon,
    WalletIcon,
    type LucideIcon,
} from 'lucide-react';
import type { AccountType, TransactionType } from '@/types';
import { tr } from '@/lib/i18n';

export const transactionTypes: Record<TransactionType, { label: string; icon: LucideIcon }> = {
    expense: { label: tr('هزینه'), icon: ArrowUpFromLineIcon },
    income: { label: tr('درآمد'), icon: ArrowDownToLineIcon },
    transfer_out: { label: tr('انتقال'), icon: ArrowLeftRightIcon },
    transfer_in: { label: tr('انتقال ورودی'), icon: ArrowLeftRightIcon },
    adjustment: { label: tr('اصلاح مانده'), icon: SlidersHorizontalIcon },
    debt_payment: { label: tr('پرداخت بدهی'), icon: HandCoinsIcon },
    receivable_collection: { label: tr('دریافت طلب'), icon: HandCoinsIcon },
    debt_given: { label: tr('قرض دادن'), icon: HandCoinsIcon },
    debt_received: { label: tr('قرض گرفتن'), icon: HandCoinsIcon },
    loan_receive: { label: tr('دریافت وام'), icon: LandmarkIcon },
    loan_installment_payment: { label: tr('پرداخت قسط'), icon: LandmarkIcon },
    check_payment: { label: tr('پرداخت چک'), icon: ReceiptTextIcon },
    check_receive: { label: tr('دریافت چک'), icon: ReceiptTextIcon },
};

/** Types offered when creating a transaction, most common first. */
export const creatableTransactionTypes: TransactionType[] = [
    'expense',
    'income',
    'transfer_out',
    'debt_payment',
    'receivable_collection',
    'loan_installment_payment',
    'check_payment',
    'check_receive',
    'adjustment',
];

export const accountTypes: Record<AccountType, { label: string; icon: LucideIcon }> = {
    bank: { label: tr('بانک'), icon: LandmarkIcon },
    card: { label: tr('کارت'), icon: CreditCardIcon },
    cash: { label: tr('نقد'), icon: BanknoteIcon },
    wallet: { label: tr('کیف پول'), icon: WalletIcon },
    other: { label: tr('سایر'), icon: CircleDollarSignIcon },
};

type Tone = 'default' | 'success' | 'warning' | 'danger' | 'muted';

export const debtStatuses: Record<string, { label: string; tone: Tone }> = {
    open: { label: tr('باز'), tone: 'default' },
    partially_settled: { label: tr('تسویه جزئی'), tone: 'warning' },
    settled: { label: tr('تسویه‌شده'), tone: 'success' },
    overdue: { label: tr('سررسید گذشته'), tone: 'danger' },
    cancelled: { label: tr('لغوشده'), tone: 'muted' },
};

export const checkStatuses: Record<string, { label: string; tone: Tone }> = {
    pending: { label: tr('در انتظار'), tone: 'warning' },
    passed: { label: tr('پاس‌شده'), tone: 'success' },
    bounced: { label: tr('برگشتی'), tone: 'danger' },
    cancelled: { label: tr('باطل‌شده'), tone: 'muted' },
};

export const installmentStatuses: Record<string, { label: string; tone: Tone }> = {
    pending: { label: tr('در انتظار'), tone: 'warning' },
    paid: { label: tr('پرداخت‌شده'), tone: 'success' },
    overdue: { label: tr('معوق'), tone: 'danger' },
    skipped: { label: tr('رد شده'), tone: 'muted' },
};

export const loanStatuses: Record<string, { label: string; tone: Tone }> = {
    active: { label: tr('فعال'), tone: 'default' },
    completed: { label: tr('تسویه‌شده'), tone: 'success' },
    cancelled: { label: tr('لغوشده'), tone: 'muted' },
};

export const reminderStatuses: Record<string, { label: string; tone: Tone }> = {
    pending: { label: tr('در انتظار'), tone: 'warning' },
    done: { label: tr('انجام‌شده'), tone: 'success' },
    dismissed: { label: tr('نادیده گرفته شد'), tone: 'muted' },
};

export const importStatuses: Record<string, { label: string; tone: Tone }> = {
    pending: { label: tr('در انتظار'), tone: 'warning' },
    processing: { label: tr('در حال پردازش'), tone: 'default' },
    completed: { label: tr('انجام‌شده'), tone: 'success' },
    failed: { label: tr('ناموفق'), tone: 'danger' },
};

export const recurringTypes: Record<string, string> = {
    expense: tr('هزینه'),
    income: tr('درآمد'),
    transfer: tr('انتقال'),
    debt: tr('طلب/بدهی'),
    loan_installment: tr('قسط'),
};

export const frequencies: Record<string, string> = {
    monthly: tr('ماهانه'),
    weekly: tr('هفتگی'),
    daily: tr('روزانه'),
    yearly: tr('سالانه'),
    custom: tr('سفارشی'),
};

export type { Tone };
