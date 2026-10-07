import { Head } from '@inertiajs/react';
import { InboxIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { ListCard, ListRow } from '@/components/list';
import { Money } from '@/components/money';
import { PageBody, PageHeader } from '@/components/page-header';
import { ListSkeleton } from '@/components/skeletons';
import { t } from '@/lib/i18n';

interface Item {
    id: number;
    title: string;
    amount: number | null;
    href: string;
}

interface Props {
    title?: string;
    items?: Item[];
}

/** Opened with an instant visit, so `title` and `items` are undefined until the server answers. */
export default function ReportCards({ title = t('گزارش‌ها'), items }: Props) {
    return (
        <>
            <Head title={title} />
            <PageHeader title={title} back={route('reports.index')} backComponent="reports/index" />
            <PageBody>
                {!items ? (
                    <ListSkeleton rows={6} />
                ) : items.length === 0 ? (
                    <EmptyState icon={InboxIcon} title={t('موردی برای گزارش نیست')} />
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
