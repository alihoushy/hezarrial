import { Link, usePage } from '@inertiajs/react';
import { ArrowLeftRightIcon, EllipsisIcon, HouseIcon, PlusIcon, WalletIcon, type LucideIcon } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { ResponsiveModal } from '@/components/responsive-modal';
import { useTheme } from '@/hooks/use-theme';
import { useLocale } from '@/lib/i18n';
import { transactionTypes } from '@/lib/labels';
import { cn } from '@/lib/utils';
import type { TransactionType } from '@/types';

/** Screens pushed on top of a tab (forms) hide the tab bar, as iOS does. */
const PAGES_WITHOUT_TAB_BAR = new Set(['transactions/form', 'accounts/form', 'categories/form', 'people/form']);

type TabKey = 'home' | 'transactions' | 'accounts' | 'more';

function activeTab(url: string): TabKey {
    const path = url.split('?')[0];

    if (path === '/' || path.startsWith('/dashboard')) return 'home';
    if (path.startsWith('/transactions')) return 'transactions';
    if (path.startsWith('/accounts')) return 'accounts';

    return 'more';
}

const QUICK_ADD: { type: TransactionType; label: string; tint: string }[] = [
    { type: 'expense', label: 'هزینه', tint: 'bg-expense/10 text-expense' },
    { type: 'income', label: 'درآمد', tint: 'bg-income/12 text-income' },
    { type: 'transfer_out', label: 'انتقال', tint: 'bg-brand/12 text-brand' },
    { type: 'debt_payment', label: 'بدهی', tint: 'bg-warning/15 text-warning' },
    { type: 'receivable_collection', label: 'طلب', tint: 'bg-income/12 text-income' },
    { type: 'loan_installment_payment', label: 'قسط', tint: 'bg-chart-3/12 text-chart-3' },
    { type: 'check_payment', label: 'پرداخت چک', tint: 'bg-expense/10 text-expense' },
    { type: 'check_receive', label: 'دریافت چک', tint: 'bg-income/12 text-income' },
];

function TabLink({ href, component, icon: Icon, label, active }: { href: string; component: string; icon: LucideIcon; label: string; active: boolean }) {
    return (
        <Link
            href={href}
            component={component}
            aria-current={active ? 'page' : undefined}
            className={cn(
                'flex flex-1 flex-col items-center justify-center gap-1 py-1.5 text-[0.7rem] font-medium transition-colors select-none',
                active ? 'text-foreground' : 'text-muted-foreground active:text-foreground',
            )}
        >
            <Icon className={cn('size-6', active && 'stroke-[2.4]')} />
            {label}
        </Link>
    );
}

function TabBar() {
    const { url } = usePage();
    const [quickAddOpen, setQuickAddOpen] = useState(false);
    const tab = activeTab(url);

    return (
        <>
            <nav className="pb-safe fixed inset-x-0 bottom-0 z-40 border-t border-border/70 bg-background/85 backdrop-blur-xl supports-backdrop-filter:bg-background/75">
                <div className="mx-auto flex h-[4.25rem] max-w-2xl items-stretch px-2">
                    <TabLink href={route('dashboard')} component="dashboard" icon={HouseIcon} label="خانه" active={tab === 'home'} />
                    <TabLink href={route('transactions.index')} component="transactions/index" icon={ArrowLeftRightIcon} label="تراکنش‌ها" active={tab === 'transactions'} />
                    <div className="flex flex-1 items-center justify-center">
                        <button
                            type="button"
                            onClick={() => setQuickAddOpen(true)}
                            aria-label="ثبت سریع"
                            className="flex size-14 -translate-y-3 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg ring-4 ring-background transition-transform active:scale-95"
                        >
                            <PlusIcon className="size-7" />
                        </button>
                    </div>
                    <TabLink href={route('accounts.index')} component="accounts/index" icon={WalletIcon} label="حساب‌ها" active={tab === 'accounts'} />
                    <TabLink href={route('settings.index')} component="settings/index" icon={EllipsisIcon} label="بیشتر" active={tab === 'more'} />
                </div>
            </nav>

            <ResponsiveModal open={quickAddOpen} onOpenChange={setQuickAddOpen} title="ثبت سریع" description="نوع تراکنش را انتخاب کنید">
                <div className="grid grid-cols-4 gap-x-2 gap-y-4 pb-4">
                    {QUICK_ADD.map(({ type, label, tint }) => {
                        const Icon = transactionTypes[type].icon;

                        return (
                            <Link
                                key={type}
                                href={route('transactions.create', { type })}
                                component="transactions/form"
                                onClick={() => setQuickAddOpen(false)}
                                className="flex flex-col items-center gap-2 rounded-xl py-1 text-center text-xs font-medium select-none active:opacity-70"
                            >
                                <span className={cn('flex size-14 items-center justify-center rounded-2xl', tint)}>
                                    <Icon className="size-6" />
                                </span>
                                {label}
                            </Link>
                        );
                    })}
                </div>
            </ResponsiveModal>
        </>
    );
}

export default function AppLayout({ children }: { children: ReactNode }) {
    const { component } = usePage();
    useLocale();
    useTheme();
    const showTabBar = !PAGES_WITHOUT_TAB_BAR.has(component);

    return (
        <div className="min-h-dvh">
            <main className={cn('mx-auto w-full max-w-2xl', showTabBar ? 'pb-tab-bar' : 'pb-[calc(2rem+env(safe-area-inset-bottom))]')}>{children}</main>
            {showTabBar && <TabBar />}
        </div>
    );
}
