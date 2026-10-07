import { Deferred, Head, Link, usePage } from '@inertiajs/react';
import { lazy, Suspense } from 'react';
import {
    ArrowDownToLineIcon,
    ArrowUpFromLineIcon,
    BellIcon,
    ChevronLeftIcon,
    HandCoinsIcon,
    LandmarkIcon,
    ReceiptTextIcon,
    SettingsIcon,
    type LucideIcon,
} from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { ListCard, ListRow } from '@/components/list';
import { Money } from '@/components/money';
import { SectionTitle } from '@/components/page-header';
import { CardsRowSkeleton, ChartSkeleton, HeroSkeleton, ListSkeleton } from '@/components/skeletons';
import { TransactionRow } from '@/components/transaction-list';
import { Button } from '@/components/ui/button';
import { todayIso, useFormat } from '@/lib/format';
import { accountTypes } from '@/lib/labels';
import { cn } from '@/lib/utils';
import type { Charts } from '@/components/dashboard-charts';
import type { Account, Transaction } from '@/types';

// Recharts is large; it loads after the page has painted.
const MonthTrend = lazy(() => import('@/components/dashboard-charts').then((module) => ({ default: module.MonthTrend })));
const CategorySpending = lazy(() => import('@/components/dashboard-charts').then((module) => ({ default: module.CategorySpending })));

interface Summary {
    income: number;
    expense: number;
    net: number;
    total_balance: number;
}

interface UpcomingItem {
    key: string;
    kind: 'reminder' | 'installment' | 'check' | 'debt';
    title: string;
    amount: number | null;
    due_date: string | null;
    href: string;
}

interface DashboardProps {
    summary?: Summary;
    accounts?: Account[];
    recent?: Transaction[];
    charts?: Charts;
    upcoming?: UpcomingItem[];
}

const UPCOMING_ICONS: Record<UpcomingItem['kind'], { icon: LucideIcon; tint: string }> = {
    reminder: { icon: BellIcon, tint: 'bg-brand/12 text-brand' },
    installment: { icon: LandmarkIcon, tint: 'bg-chart-3/12 text-chart-3' },
    check: { icon: ReceiptTextIcon, tint: 'bg-warning/15 text-warning' },
    debt: { icon: HandCoinsIcon, tint: 'bg-expense/10 text-expense' },
};

function Greeting() {
    const { auth } = usePage().props;
    const format = useFormat();

    return (
        <div className="pt-safe flex items-center justify-between gap-3 px-4 pt-5">
            <div className="min-w-0">
                <p className="text-sm text-muted-foreground">{format.longDate(todayIso())}</p>
                <h1 className="truncate text-2xl font-extrabold">سلام{auth.user ? `، ${auth.user.name}` : ''}</h1>
            </div>
            <Button variant="secondary" size="icon" className="rounded-full" asChild>
                <Link href={route('settings.index')} component="settings/index" aria-label="تنظیمات">
                    <SettingsIcon className="size-5" />
                </Link>
            </Button>
        </div>
    );
}

function BalanceHero({ summary }: { summary: Summary }) {
    const format = useFormat();

    return (
        <section className="relative overflow-hidden rounded-3xl bg-linear-to-br from-zinc-900 via-zinc-900 to-teal-900 p-5 text-white shadow-lg ring-1 ring-white/10">
            <div className="pointer-events-none absolute -top-16 -end-10 size-48 rounded-full bg-teal-400/20 blur-3xl" />
            <p className="text-sm text-white/70">موجودی کل حساب‌ها</p>
            <p className="mt-1 text-[2rem] leading-tight font-extrabold">
                <span className="tabular-nums" dir="ltr">
                    {summary.total_balance < 0 ? '−' : ''}
                    {format.money(Math.abs(summary.total_balance))}
                </span>
                <span className="ms-1.5 text-sm font-medium text-white/60">{format.unit}</span>
            </p>
            {format.secondary(summary.total_balance) && <p className="text-xs text-white/50">{format.secondary(summary.total_balance)}</p>}

            <div className="mt-5 grid grid-cols-2 gap-3">
                <div className="rounded-2xl bg-white/8 p-3 ring-1 ring-white/10">
                    <p className="flex items-center gap-1.5 text-xs text-white/70">
                        <ArrowDownToLineIcon className="size-3.5 text-emerald-300" />
                        درآمد این ماه
                    </p>
                    <p className="mt-1 font-bold tabular-nums">{format.money(summary.income)}</p>
                </div>
                <div className="rounded-2xl bg-white/8 p-3 ring-1 ring-white/10">
                    <p className="flex items-center gap-1.5 text-xs text-white/70">
                        <ArrowUpFromLineIcon className="size-3.5 text-rose-300" />
                        هزینه این ماه
                    </p>
                    <p className="mt-1 font-bold tabular-nums">{format.money(summary.expense)}</p>
                </div>
            </div>
            <p className="mt-3 text-xs text-white/60">
                خالص این ماه:{' '}
                <span className={cn('font-semibold', summary.net >= 0 ? 'text-emerald-300' : 'text-rose-300')}>
                    <span className="tabular-nums" dir="ltr">
                        {summary.net < 0 ? '−' : ''}
                        {format.money(Math.abs(summary.net))}
                    </span>{' '}
                    {format.unit}
                </span>
            </p>
        </section>
    );
}

function AccountsStrip({ accounts }: { accounts: Account[] }) {
    if (accounts.length === 0) {
        return (
            <EmptyState
                icon={LandmarkIcon}
                title="هنوز حسابی ندارید"
                description="برای شروع، حساب بانکی، کارت یا کیف پول نقد خود را اضافه کنید."
                action={
                    <Button asChild>
                        <Link href={route('accounts.create')} component="accounts/form">
                            افزودن حساب
                        </Link>
                    </Button>
                }
            />
        );
    }

    return (
        <div className="no-scrollbar -mx-4 flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-px-4 px-4 pb-1">
            {accounts.map((account) => {
                const type = accountTypes[account.type];

                return (
                    <Link
                        key={account.id}
                        href={route('accounts.show', account.id)}
                        component="accounts/show"
                        pageProps={{ account }}
                        className="flex w-[68%] max-w-64 shrink-0 snap-start flex-col gap-4 rounded-2xl bg-card p-4 ring-1 ring-foreground/5 transition-transform select-none active:scale-[0.98]"
                    >
                        <span className="flex items-center gap-2 text-sm font-medium">
                            <span className="flex size-8 items-center justify-center rounded-full bg-muted">
                                <type.icon className="size-4" />
                            </span>
                            <span className="truncate">{account.name}</span>
                        </span>
                        <Money amount={account.current_balance} className="text-lg font-bold" />
                    </Link>
                );
            })}
        </div>
    );
}

function Upcoming({ items }: { items: UpcomingItem[] }) {
    const format = useFormat();

    if (items.length === 0) {
        return <p className="rounded-2xl bg-card px-4 py-6 text-center text-sm text-muted-foreground ring-1 ring-foreground/5">موعد نزدیکی ندارید.</p>;
    }

    return (
        <ListCard>
            {items.map((item) => {
                const { icon, tint } = UPCOMING_ICONS[item.kind];

                return (
                    <ListRow
                        key={item.key}
                        href={item.href}
                        icon={icon}
                        iconClassName={tint}
                        title={item.title}
                        subtitle={item.due_date ? format.relativeDay(item.due_date) : 'بدون سررسید'}
                        trailing={item.amount !== null ? <Money amount={item.amount} className="text-sm font-semibold" /> : undefined}
                    />
                );
            })}
        </ListCard>
    );
}

export default function Dashboard({ summary, accounts, recent, charts, upcoming }: DashboardProps) {
    return (
        <>
            <Head title="خانه" />
            <Greeting />
            <div className="flex flex-col gap-7 px-4 pt-4">
                {summary ? <BalanceHero summary={summary} /> : <HeroSkeleton className="h-56" />}

                <section>
                    <SectionTitle
                        action={
                            <Link href={route('accounts.index')} component="accounts/index" className="flex items-center text-sm font-medium text-brand">
                                همه
                                <ChevronLeftIcon className="size-4" />
                            </Link>
                        }
                    >
                        حساب‌ها
                    </SectionTitle>
                    {accounts ? <AccountsStrip accounts={accounts} /> : <CardsRowSkeleton />}
                </section>

                <section className="flex flex-col gap-3">
                    <SectionTitle>این ماه</SectionTitle>
                    <Deferred data="charts" fallback={<ChartSkeleton />}>
                        <Suspense fallback={<ChartSkeleton />}>{charts && <MonthTrend daily={charts.daily} />}</Suspense>
                    </Deferred>
                    <Deferred data="charts" fallback={<ChartSkeleton className="h-44" />}>
                        <Suspense fallback={<ChartSkeleton className="h-44" />}>{charts && <CategorySpending categories={charts.categories} />}</Suspense>
                    </Deferred>
                </section>

                <section>
                    <SectionTitle>موعدهای نزدیک</SectionTitle>
                    <Deferred data="upcoming" fallback={<ListSkeleton rows={3} />}>
                        {upcoming && <Upcoming items={upcoming} />}
                    </Deferred>
                </section>

                <section>
                    <SectionTitle
                        action={
                            <Link href={route('transactions.index')} component="transactions/index" className="flex items-center text-sm font-medium text-brand">
                                همه
                                <ChevronLeftIcon className="size-4" />
                            </Link>
                        }
                    >
                        آخرین تراکنش‌ها
                    </SectionTitle>
                    {recent ? (
                        recent.length > 0 ? (
                            <ListCard>
                                {recent.map((transaction) => (
                                    <TransactionRow key={transaction.id} transaction={transaction} />
                                ))}
                            </ListCard>
                        ) : (
                            <p className="rounded-2xl bg-card px-4 py-6 text-center text-sm text-muted-foreground ring-1 ring-foreground/5">هنوز تراکنشی ثبت نشده است.</p>
                        )
                    ) : (
                        <ListSkeleton rows={4} />
                    )}
                </section>
            </div>
        </>
    );
}
