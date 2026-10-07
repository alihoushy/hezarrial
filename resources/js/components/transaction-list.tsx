import { Money } from '@/components/money';
import { ListCard, ListRow } from '@/components/list';
import { useFormat } from '@/lib/format';
import { transactionTypes } from '@/lib/labels';
import type { Transaction } from '@/types';

export function TransactionRow({ transaction }: { transaction: Transaction }) {
    const type = transactionTypes[transaction.type];
    const incoming = transaction.direction > 0;
    const title = transaction.description || transaction.category?.name || type.label;
    const subtitle = [transaction.account?.name, transaction.description && transaction.category?.name, type.label !== title ? type.label : null]
        .filter(Boolean)
        .join(' · ');

    return (
        <ListRow
            href={route('transactions.show', transaction.id)}
            component="transactions/show"
            pageProps={{ transaction }}
            icon={type.icon}
            iconClassName={incoming ? 'bg-income/12 text-income' : 'bg-expense/10 text-expense'}
            title={title}
            subtitle={subtitle}
            trailing={<Money amount={transaction.amount} tone={incoming ? 'income' : 'expense'} showSign className="text-[0.95rem] font-semibold" />}
        />
    );
}

/** Transactions grouped under day headings (امروز، دیروز، …), newest first. */
export function GroupedTransactions({ transactions }: { transactions: Transaction[] }) {
    const format = useFormat();
    const groups = new Map<string, Transaction[]>();

    for (const transaction of transactions) {
        const key = transaction.date ?? '';
        groups.set(key, [...(groups.get(key) ?? []), transaction]);
    }

    return (
        <div className="flex flex-col gap-5">
            {[...groups.entries()].map(([date, items]) => (
                <section key={date || 'undated'}>
                    <h3 className="px-1 pb-2 text-xs font-semibold text-muted-foreground">{date ? format.relativeDay(date) : 'بدون تاریخ'}</h3>
                    <ListCard>
                        {items.map((transaction) => (
                            <TransactionRow key={transaction.id} transaction={transaction} />
                        ))}
                    </ListCard>
                </section>
            ))}
        </div>
    );
}
