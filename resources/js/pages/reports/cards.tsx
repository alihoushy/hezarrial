import { Head } from '@inertiajs/react';
import { InboxIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { ListCard, ListRow } from '@/components/list';
import { Money } from '@/components/money';
import { PageBody, PageHeader } from '@/components/page-header';

interface Item {
    id: number;
    title: string;
    amount: number | null;
    href: string;
}

export default function ReportCards({ title, items }: { title: string; items: Item[] }) {
    return (
        <>
            <Head title={title} />
            <PageHeader title={title} back={route('reports.index')} backComponent="reports/index" />
            <PageBody>
                {items.length === 0 ? (
                    <EmptyState icon={InboxIcon} title="موردی برای گزارش نیست" />
                ) : (
                    <ListCard>
                        {items.map((item) => (
                            <ListRow key={item.id} href={item.href} title={item.title} trailing={item.amount !== null ? <Money amount={item.amount} className="text-sm font-semibold" /> : undefined} chevron />
                        ))}
                    </ListCard>
                )}
            </PageBody>
        </>
    );
}
