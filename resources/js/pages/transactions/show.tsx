import { Head, Link } from '@inertiajs/react';
import { PencilIcon, Trash2Icon } from 'lucide-react';
import { ConfirmAction } from '@/components/confirm-action';
import { ListCard, ListRow } from '@/components/list';
import { Money } from '@/components/money';
import { PageBody, PageHeader } from '@/components/page-header';
import { DetailSkeleton } from '@/components/skeletons';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFormat } from '@/lib/format';
import { transactionTypes } from '@/lib/labels';
import { cn } from '@/lib/utils';
import type { Transaction } from '@/types';

export default function TransactionShow({ transaction }: { transaction?: Transaction }) {
    const format = useFormat();

    return (
        <>
            <Head title="جزئیات تراکنش" />
            <PageHeader
                title="جزئیات تراکنش"
                back={route('transactions.index')}
                backComponent="transactions/index"
                actions={
                    transaction && (
                        <Button variant="ghost" size="icon" className="rounded-full" asChild>
                            <Link href={route('transactions.edit', transaction.id)} component="transactions/form" aria-label="ویرایش">
                                <PencilIcon className="size-5" />
                            </Link>
                        </Button>
                    )
                }
            />
            <PageBody>
                {!transaction ? (
                    <DetailSkeleton />
                ) : (
                    <TransactionDetails transaction={transaction} format={format} />
                )}
            </PageBody>
        </>
    );
}

function TransactionDetails({ transaction, format }: { transaction: Transaction; format: ReturnType<typeof useFormat> }) {
    const type = transactionTypes[transaction.type];
    const incoming = transaction.direction > 0;

    const rows: [string, string | null | undefined][] = [
        ['حساب', transaction.account?.name],
        ['دسته‌بندی', transaction.category?.name],
        ['شخص', transaction.person?.name],
        ['تاریخ', format.longDate(transaction.date)],
        ['ساعت', transaction.time ? format.digits(transaction.time) : null],
        ['شماره پیگیری', transaction.reference_number ? format.digits(transaction.reference_number) : null],
    ];

    return (
        <>
            <section className="flex flex-col items-center gap-3 rounded-3xl bg-card px-5 py-7 text-center ring-1 ring-foreground/5">
                <span className={cn('flex size-14 items-center justify-center rounded-full', incoming ? 'bg-income/12 text-income' : 'bg-expense/10 text-expense')}>
                    <type.icon className="size-6" />
                </span>
                <Badge variant="secondary">{type.label}</Badge>
                <Money amount={transaction.amount} tone={incoming ? 'income' : 'expense'} showSign withSecondary className="items-center text-3xl font-extrabold" />
                {transaction.description && <p className="max-w-xs text-sm text-muted-foreground">{transaction.description}</p>}
            </section>

            <ListCard>
                {rows
                    .filter(([, value]) => value)
                    .map(([label, value]) => (
                        <ListRow key={label} className="min-h-13" title={<span className="text-sm text-muted-foreground">{label}</span>} trailing={<span className="text-sm font-medium">{value}</span>} />
                    ))}
            </ListCard>

            <ConfirmAction
                title="حذف تراکنش؟"
                description="مانده حساب بر اساس حذف این تراکنش به‌روز می‌شود."
                confirmLabel="حذف"
                href={route('transactions.destroy', transaction.id)}
                method="delete"
                destructive
            >
                <Button variant="destructive" size="lg" className="w-full">
                    <Trash2Icon />
                    حذف تراکنش
                </Button>
            </ConfirmAction>
        </>
    );
}
