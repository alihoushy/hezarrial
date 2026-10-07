import { Head, InfiniteScroll, Link } from '@inertiajs/react';
import { PencilIcon, ReceiptTextIcon, RefreshCwIcon } from 'lucide-react';
import { ConfirmAction } from '@/components/confirm-action';
import { EmptyState } from '@/components/empty-state';
import { PageBody, PageHeader, SectionTitle } from '@/components/page-header';
import { HeroSkeleton, ListSkeleton } from '@/components/skeletons';
import { GroupedTransactions } from '@/components/transaction-list';
import { Button } from '@/components/ui/button';
import { useFormat } from '@/lib/format';
import { accountTypes } from '@/lib/labels';
import type { Account, Paginated, Transaction } from '@/types';

interface Props {
    account?: Account;
    transactions?: Paginated<Transaction>;
}

export default function AccountShow({ account, transactions }: Props) {
    const format = useFormat();
    const type = account ? accountTypes[account.type] : null;

    return (
        <>
            <Head title={account?.name ?? 'حساب'} />
            <PageHeader
                title={account?.name ?? 'حساب'}
                back={route('accounts.index')}
                backComponent="accounts/index"
                actions={
                    account && (
                        <Button variant="ghost" size="icon" className="rounded-full" asChild>
                            <Link href={route('accounts.edit', account.id)} component="accounts/form" aria-label="ویرایش حساب">
                                <PencilIcon className="size-5" />
                            </Link>
                        </Button>
                    )
                }
            />
            <PageBody>
                {account && type ? (
                    <section className="rounded-3xl bg-linear-to-br from-zinc-900 to-teal-900 p-5 text-white shadow-lg ring-1 ring-white/10">
                        <div className="flex items-center gap-2 text-sm text-white/75">
                            <type.icon className="size-4" />
                            {[type.label, account.bank_name, account.card_last_four ? `•••• ${format.digits(account.card_last_four)}` : null].filter(Boolean).join(' · ')}
                        </div>
                        <p className="mt-3 text-sm text-white/60">موجودی فعلی</p>
                        <p className="text-[2rem] leading-tight font-extrabold">
                            <span className="tabular-nums" dir="ltr">
                                {account.current_balance < 0 ? '−' : ''}
                                {format.money(Math.abs(account.current_balance))}
                            </span>
                            <span className="ms-1.5 text-sm font-medium text-white/60">{format.unit}</span>
                        </p>
                        <p className="mt-2 text-xs text-white/50">
                            مانده افتتاحیه: {format.money(account.opening_balance)} {format.unit}
                        </p>
                        <ConfirmAction
                            title="بازسازی مانده؟"
                            description="مانده این حساب از روی مانده افتتاحیه و همه تراکنش‌ها دوباره محاسبه و اصلاح می‌شود."
                            confirmLabel="بازسازی"
                            href={route('accounts.recalculate', account.id)}
                        >
                            <Button variant="secondary" size="sm" className="mt-4 bg-white/10 text-white hover:bg-white/20">
                                <RefreshCwIcon />
                                بازسازی مانده
                            </Button>
                        </ConfirmAction>
                    </section>
                ) : (
                    <HeroSkeleton className="h-48" />
                )}

                <section>
                    <SectionTitle>گردش حساب</SectionTitle>
                    {!transactions ? (
                        <ListSkeleton rows={6} />
                    ) : transactions.data.length === 0 ? (
                        <EmptyState icon={ReceiptTextIcon} title="تراکنشی ثبت نشده" description="تراکنش‌های این حساب اینجا نمایش داده می‌شوند." />
                    ) : (
                        <InfiniteScroll data="transactions" preserveUrl buffer={400} loading={<ListSkeleton rows={3} className="mt-5" />}>
                            <GroupedTransactions transactions={transactions.data} />
                        </InfiniteScroll>
                    )}
                </section>
            </PageBody>
        </>
    );
}
