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

export const transactionTypes: Record<TransactionType, { label: string; icon: LucideIcon }> = {
    expense: { label: 'هزینه', icon: ArrowUpFromLineIcon },
    income: { label: 'درآمد', icon: ArrowDownToLineIcon },
    transfer_out: { label: 'انتقال', icon: ArrowLeftRightIcon },
    transfer_in: { label: 'انتقال ورودی', icon: ArrowLeftRightIcon },
    adjustment: { label: 'اصلاح مانده', icon: SlidersHorizontalIcon },
    debt_payment: { label: 'پرداخت بدهی', icon: HandCoinsIcon },
    receivable_collection: { label: 'دریافت طلب', icon: HandCoinsIcon },
    debt_given: { label: 'قرض دادن', icon: HandCoinsIcon },
    debt_received: { label: 'قرض گرفتن', icon: HandCoinsIcon },
    loan_receive: { label: 'دریافت وام', icon: LandmarkIcon },
    loan_installment_payment: { label: 'پرداخت قسط', icon: LandmarkIcon },
    check_payment: { label: 'پرداخت چک', icon: ReceiptTextIcon },
    check_receive: { label: 'دریافت چک', icon: ReceiptTextIcon },
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
    bank: { label: 'بانک', icon: LandmarkIcon },
    card: { label: 'کارت', icon: CreditCardIcon },
    cash: { label: 'نقد', icon: BanknoteIcon },
    wallet: { label: 'کیف پول', icon: WalletIcon },
    other: { label: 'سایر', icon: CircleDollarSignIcon },
};

type Tone = 'default' | 'success' | 'warning' | 'danger' | 'muted';

export const debtStatuses: Record<string, { label: string; tone: Tone }> = {
    open: { label: 'باز', tone: 'default' },
    partially_settled: { label: 'تسویه جزئی', tone: 'warning' },
    settled: { label: 'تسویه‌شده', tone: 'success' },
    overdue: { label: 'سررسید گذشته', tone: 'danger' },
    cancelled: { label: 'لغوشده', tone: 'muted' },
};

export const checkStatuses: Record<string, { label: string; tone: Tone }> = {
    pending: { label: 'در انتظار', tone: 'warning' },
    passed: { label: 'پاس‌شده', tone: 'success' },
    bounced: { label: 'برگشتی', tone: 'danger' },
    cancelled: { label: 'باطل‌شده', tone: 'muted' },
};

export const installmentStatuses: Record<string, { label: string; tone: Tone }> = {
    pending: { label: 'در انتظار', tone: 'warning' },
    paid: { label: 'پرداخت‌شده', tone: 'success' },
    overdue: { label: 'معوق', tone: 'danger' },
    skipped: { label: 'رد شده', tone: 'muted' },
};

export const loanStatuses: Record<string, { label: string; tone: Tone }> = {
    active: { label: 'فعال', tone: 'default' },
    completed: { label: 'تسویه‌شده', tone: 'success' },
    cancelled: { label: 'لغوشده', tone: 'muted' },
};

export const reminderStatuses: Record<string, { label: string; tone: Tone }> = {
    pending: { label: 'در انتظار', tone: 'warning' },
    done: { label: 'انجام‌شده', tone: 'success' },
    dismissed: { label: 'نادیده گرفته شد', tone: 'muted' },
};

export const importStatuses: Record<string, { label: string; tone: Tone }> = {
    pending: { label: 'در انتظار', tone: 'warning' },
    processing: { label: 'در حال پردازش', tone: 'default' },
    completed: { label: 'انجام‌شده', tone: 'success' },
    failed: { label: 'ناموفق', tone: 'danger' },
};

export const recurringTypes: Record<string, string> = {
    expense: 'هزینه',
    income: 'درآمد',
    transfer: 'انتقال',
    debt: 'طلب/بدهی',
    loan_installment: 'قسط',
};

export const frequencies: Record<string, string> = {
    monthly: 'ماهانه',
    weekly: 'هفتگی',
    daily: 'روزانه',
    yearly: 'سالانه',
    custom: 'سفارشی',
};

export type { Tone };
