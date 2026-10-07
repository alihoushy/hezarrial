import { Head, Link } from '@inertiajs/react';
import { PencilIcon, Trash2Icon } from 'lucide-react';
import { ConfirmAction } from '@/components/confirm-action';
import { ListCard, ListRow, StatusBadge } from '@/components/list';
import { Money } from '@/components/money';
import { PageBody, PageHeader, SectionTitle } from '@/components/page-header';
import { PersonAvatar } from '@/components/person-avatar';
import { DetailSkeleton } from '@/components/skeletons';
import { TransactionRow } from '@/components/transaction-list';
import { Button } from '@/components/ui/button';
import { useFormat } from '@/lib/format';
import { debtStatuses } from '@/lib/labels';
import type { Debt, Person, Transaction } from '@/types';

interface Props {
    person?: Person;
    summary?: { payable: number; receivable: number; net: number };
    openItems?: Debt[];
    settledItems?: Debt[];
    transactions?: Transaction[];
}

function DebtRow({ debt }: { debt: Debt }) {
    const format = useFormat();
    const status = debtStatuses[debt.status];
    const payable = debt.type === 'payable';

    return (
        <ListRow
            title={payable ? 'بدهی من' : 'طلب من'}
            subtitle={debt.due_date ? `سررسید: ${format.date(debt.due_date)}` : 'بدون سررسید'}
            trailing={
                <>
                    <Money amount={debt.remaining_amount} tone={payable ? 'expense' : 'income'} className="text-sm font-semibold" />
                    <span className="mt-1">
                        <StatusBadge label={status.label} tone={status.tone} />
                    </span>
                </>
            }
        />
    );
}

export default function PersonShow({ person, summary, openItems, settledItems, transactions }: Props) {
    return (
        <>
            <Head title={person?.full_name ?? 'پرونده شخص'} />
            <PageHeader
                title="پرونده شخص"
                back={route('people.index')}
                backComponent="people/index"
                actions={
                    person && (
                        <Button variant="ghost" size="icon" className="rounded-full" asChild>
                            <Link href={route('people.edit', person.id)} component="people/form" aria-label="ویرایش">
                                <PencilIcon className="size-5" />
                            </Link>
                        </Button>
                    )
                }
            />
            <PageBody>
                {!person || !summary ? (
                    <DetailSkeleton />
                ) : (
                    <>
                        <section className="flex flex-col items-center gap-2 rounded-3xl bg-card px-5 py-6 text-center ring-1 ring-foreground/5">
                            <PersonAvatar name={person.full_name} className="size-16 text-2xl" />
                            <h2 className="text-xl font-extrabold">{person.full_name}</h2>
                            {person.mobile && (
                                <a href={`tel:${person.mobile}`} dir="ltr" className="text-sm text-brand tabular-nums">
                                    {person.mobile}
                                </a>
                            )}
                            {person.description && <p className="max-w-xs text-sm text-muted-foreground">{person.description}</p>}
                            <div className="mt-3 grid w-full grid-cols-3 gap-2 text-center">
                                <div className="rounded-2xl bg-muted/70 p-3">
                                    <p className="text-xs text-muted-foreground">بدهی من</p>
                                    <Money amount={summary.payable} className="mt-1 text-sm font-bold" unitClassName="hidden" />
                                </div>
                                <div className="rounded-2xl bg-muted/70 p-3">
                                    <p className="text-xs text-muted-foreground">طلب من</p>
                                    <Money amount={summary.receivable} className="mt-1 text-sm font-bold" unitClassName="hidden" />
                                </div>
                                <div className="rounded-2xl bg-muted/70 p-3">
                                    <p className="text-xs text-muted-foreground">خالص</p>
                                    <Money amount={summary.net} tone="signed" className="mt-1 text-sm font-bold" unitClassName="hidden" />
                                </div>
                            </div>
                        </section>

                        <section>
                            <SectionTitle>موارد باز</SectionTitle>
                            {openItems && openItems.length > 0 ? (
                                <ListCard>
                                    {openItems.map((debt) => (
                                        <DebtRow key={debt.id} debt={debt} />
                                    ))}
                                </ListCard>
                            ) : (
                                <p className="rounded-2xl bg-card px-4 py-6 text-center text-sm text-muted-foreground ring-1 ring-foreground/5">مورد بازی وجود ندارد.</p>
                            )}
                        </section>

                        {settledItems && settledItems.length > 0 && (
                            <section>
                                <SectionTitle>تسویه‌شده</SectionTitle>
                                <ListCard className="opacity-80">
                                    {settledItems.map((debt) => (
                                        <DebtRow key={debt.id} debt={debt} />
                                    ))}
                                </ListCard>
                            </section>
                        )}

                        <section>
                            <SectionTitle>تراکنش‌های مرتبط</SectionTitle>
                            {transactions && transactions.length > 0 ? (
                                <ListCard>
                                    {transactions.map((transaction) => (
                                        <TransactionRow key={transaction.id} transaction={transaction} />
                                    ))}
                                </ListCard>
                            ) : (
                                <p className="rounded-2xl bg-card px-4 py-6 text-center text-sm text-muted-foreground ring-1 ring-foreground/5">تراکنشی برای این شخص ثبت نشده است.</p>
                            )}
                        </section>

                        <ConfirmAction
                            title="حذف شخص؟"
                            description="پرونده این شخص حذف می‌شود."
                            confirmLabel="حذف"
                            href={route('people.destroy', person.id)}
                            method="delete"
                            destructive
                        >
                            <Button variant="destructive" size="lg" className="w-full">
                                <Trash2Icon />
                                حذف شخص
                            </Button>
                        </ConfirmAction>
                    </>
                )}
            </PageBody>
        </>
    );
}
