import { Head, InfiniteScroll, Link } from '@inertiajs/react';
import { PlusIcon, ReceiptTextIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageBody, PageHeader } from '@/components/page-header';
import { ListSkeleton } from '@/components/skeletons';
import { GroupedTransactions } from '@/components/transaction-list';
import { Button } from '@/components/ui/button';
import type { Paginated, Transaction } from '@/types';
import { t } from '@/lib/i18n';

export default function TransactionsIndex({ transactions }: { transactions?: Paginated<Transaction> }) {
    return (
        <>
            <Head title={t('تراکنش‌ها')} />
            <PageHeader
                title={t('تراکنش‌ها')}
                actions={
                    <Button size="icon" variant="ghost" className="rounded-full" asChild>
                        <Link href={route('transactions.create')} component="transactions/form" aria-label={t('ثبت تراکنش')}>
                            <PlusIcon className="size-6" />
                        </Link>
                    </Button>
                }
            />
            <PageBody>
                {!transactions ? (
                    <ListSkeleton rows={8} />
                ) : transactions.data.length === 0 ? (
                    <EmptyState
                        icon={ReceiptTextIcon}
                        title={t('هنوز تراکنشی ثبت نشده')}
                        description={t('اولین درآمد یا هزینه خود را ثبت کنید تا اینجا نمایش داده شود.')}
                        action={
                            <Button asChild>
                                <Link href={route('transactions.create')} component="transactions/form">
                                    {t('ثبت تراکنش')}
                                </Link>
                            </Button>
                        }
                    />
                ) : (
                    <InfiniteScroll data="transactions" preserveUrl buffer={400} loading={<ListSkeleton rows={3} className="mt-5" />}>
                        <GroupedTransactions transactions={transactions.data} />
                    </InfiniteScroll>
                )}
            </PageBody>
        </>
    );
}
