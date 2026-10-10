import '@inertiajs/core';
import type { route as routeFn } from 'ziggy-js';

export type CurrencyDisplay = 'rial' | 'toman' | 'both';
export type Theme = 'system' | 'light' | 'dark';

export interface User {
    id: number;
    name: string;
    email: string | null;
    mobile: string | null;
}

export interface Settings {
    currency_display: CurrencyDisplay;
    persian_digits: boolean;
    theme: Theme;
}

export interface LocaleInfo {
    code: string;
    dir: 'rtl' | 'ltr';
}

export interface SharedProps {
    locale: LocaleInfo;
    locales: { code: string; name: string }[];
    registration: boolean;
    auth: { user: User | null };
    settings: Settings;
    csrf_token: string;
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        errorValueType: string;
        flashDataType: { status?: string; sms_token?: string };
        sharedPageProps: SharedProps;
    }
}

declare global {
    const route: typeof routeFn;
}

export interface Option {
    id: number;
    name: string;
}

export interface PersonOption {
    id: number;
    full_name: string;
}

export type TransactionType =
    | 'income'
    | 'expense'
    | 'transfer_in'
    | 'transfer_out'
    | 'adjustment'
    | 'debt_given'
    | 'debt_received'
    | 'debt_payment'
    | 'receivable_collection'
    | 'loan_receive'
    | 'loan_installment_payment'
    | 'check_payment'
    | 'check_receive';

export type AccountType = 'bank' | 'cash' | 'wallet' | 'card' | 'other';
export type CategoryType = 'income' | 'expense';

export interface BankOption {
    value: string;
    label: string;
}

export interface Account {
    id: number;
    name: string;
    type: AccountType;
    bank: string | null;
    bank_label: string | null;
    bank_name: string | null;
    card_last_four: string | null;
    opening_balance: number;
    current_balance: number;
    is_active: boolean;
}

export interface Transaction {
    id: number;
    type: TransactionType;
    direction: 1 | -1;
    amount: number;
    date: string | null;
    time: string | null;
    description: string | null;
    reference_number: string | null;
    account: Option | null;
    category: (Option & { color: string | null }) | null;
    person: Option | null;
}

export interface Category {
    id: number;
    name: string;
    type: CategoryType;
    color: string | null;
    parent_id: number | null;
    is_active: boolean;
}

export interface CategoryOption extends Option {
    type: CategoryType;
    color?: string | null;
}

export interface Person {
    id: number;
    full_name: string;
    mobile: string | null;
    description: string | null;
}

export type DebtStatus = 'open' | 'partially_settled' | 'settled' | 'overdue' | 'cancelled';

export interface Debt {
    id: number;
    type: 'payable' | 'receivable';
    status: DebtStatus;
    original_amount: number;
    remaining_amount: number;
    due_date: string | null;
    description: string | null;
    person: Option | null;
}

export interface Loan {
    id: number;
    title: string;
    lender_name: string | null;
    status: 'active' | 'completed' | 'cancelled';
    principal_amount: number;
    total_payable_amount: number;
    installment_amount: number;
    installment_count: number;
    paid_installment_count: number;
    start_date: string | null;
}

export interface Installment {
    id: number;
    due_date: string | null;
    amount: number;
    status: 'pending' | 'paid' | 'overdue' | 'skipped';
    paid_at: string | null;
}

export interface Check {
    id: number;
    type: 'payable' | 'receivable';
    status: 'pending' | 'passed' | 'bounced' | 'cancelled';
    amount: number;
    due_date: string | null;
    check_number: string | null;
    bank: string | null;
    bank_label: string | null;
    bank_name: string | null;
    account: Option | null;
    person: Option | null;
}

export interface Budget {
    id: number;
    title: string;
    amount: number;
    start_date: string | null;
    end_date: string | null;
    category: Option | null;
    progress: { spent: number; remaining: number; percent: number; over_threshold: boolean };
}

export interface Reminder {
    id: number;
    title: string;
    due_date: string | null;
    due_time: string | null;
    status: 'pending' | 'done' | 'dismissed';
}

export interface RecurringItem {
    id: number;
    title: string;
    type: string;
    amount: number;
    frequency: string;
    next_run_date: string | null;
    end_date: string | null;
    is_active: boolean;
    description: string | null;
}

export interface Backup {
    id: number;
    file_name: string;
    file_size: number;
    created_at: string | null;
}

export interface ImportRecord {
    id: number;
    type: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    total_rows: number;
    imported_rows: number;
    created_at: string | null;
}

export interface SmsPattern {
    id: number;
    name: string;
    bank_name: string | null;
    is_active: boolean;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
}
