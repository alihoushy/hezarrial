import { Head, useForm } from '@inertiajs/react';
import { CheckIcon } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { DateInput, FormField, SelectField, SubmitButton } from '@/components/form-field';
import { ListCard, ListRow, StatusBadge } from '@/components/list';
import { Money } from '@/components/money';
import { PageBody, PageHeader, SectionTitle } from '@/components/page-header';
import { ResponsiveModal } from '@/components/responsive-modal';
import { DetailSkeleton, ListSkeleton } from '@/components/skeletons';
import { FieldGroup } from '@/components/ui/field';
import { Progress } from '@/components/ui/progress';
import { todayIso, useFormat } from '@/lib/format';
import { installmentStatuses } from '@/lib/labels';
import { cn } from '@/lib/utils';
import type { Installment, Loan, Option } from '@/types';
import { t } from '@/lib/i18n';

interface Props {
    loan?: Loan;
    installments?: Installment[];
    accounts?: Option[];
}

function PaySheet({ loanId, installment, accounts, onClose }: { loanId: number; installment: Installment | null; accounts: Option[]; onClose: () => void }) {
    const form = useForm({ account_id: String(accounts[0]?.id ?? ''), transaction_date: todayIso() });
    const format = useFormat();

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (!installment) {
            return;
        }

        form.post(route('loans.installments.pay', { loan: loanId, installment: installment.id }), {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <ResponsiveModal
            open={installment !== null}
            onOpenChange={(next) => !next && onClose()}
            title={t('پرداخت قسط')}
            description={installment ? `${format.date(installment.due_date)} · ${format.money(installment.amount)} ${format.unit}` : undefined}
        >
            <form onSubmit={submit} noValidate className="pb-4">
                <FieldGroup className="gap-5">
                    <FormField label={t('پرداخت از حساب')} htmlFor="pay_account" error={form.errors.account_id}>
                        <SelectField id="pay_account" value={form.data.account_id} onChange={(event) => form.setData('account_id', event.target.value)} options={accounts.map((account) => ({ value: account.id, label: account.name }))} />
                    </FormField>
                    <FormField label={t('تاریخ پرداخت')} htmlFor="pay_date" error={form.errors.transaction_date}>
                        <DateInput id="pay_date" value={form.data.transaction_date} onChange={(event) => form.setData('transaction_date', event.target.value)} />
                    </FormField>
                    <SubmitButton size="lg" processing={form.processing}>
                        {t('ثبت پرداخت')}
                    </SubmitButton>
                </FieldGroup>
            </form>
        </ResponsiveModal>
    );
}

export default function LoanShow({ loan, installments, accounts }: Props) {
    const format = useFormat();
    const [paying, setPaying] = useState<Installment | null>(null);
    const percent = loan && loan.installment_count ? (loan.paid_installment_count / loan.installment_count) * 100 : 0;

    return (
        <>
            <Head title={loan?.title ?? t('وام')} />
            <PageHeader title={loan?.title ?? t('وام')} back={route('loans.index')} backComponent="loans/index" />
            <PageBody>
                {!loan ? (
                    <DetailSkeleton />
                ) : (
                    <section className="flex flex-col gap-4 rounded-3xl bg-card p-5 ring-1 ring-foreground/5">
                        <div>
                            <p className="text-sm text-muted-foreground">{t('مبلغ کل قابل پرداخت')}</p>
                            <Money amount={loan.total_payable_amount} withSecondary className="text-[1.75rem] font-extrabold" />
                        </div>
                        <Progress value={percent} aria-label={t('پیشرفت پرداخت')} />
                        <div className="grid grid-cols-3 gap-2 text-center">
                            <div className="rounded-2xl bg-muted/70 p-3">
                                <p className="text-xs text-muted-foreground">{t('اصل وام')}</p>
                                <Money amount={loan.principal_amount} className="mt-1 text-sm font-bold" unitClassName="hidden" />
                            </div>
                            <div className="rounded-2xl bg-muted/70 p-3">
                                <p className="text-xs text-muted-foreground">{t('هر قسط')}</p>
                                <Money amount={loan.installment_amount} className="mt-1 text-sm font-bold" unitClassName="hidden" />
                            </div>
                            <div className="rounded-2xl bg-muted/70 p-3">
                                <p className="text-xs text-muted-foreground">{t('پرداخت‌شده')}</p>
                                <p className="mt-1 text-sm font-bold tabular-nums">
                                    {format.number(loan.paid_installment_count)}/{format.number(loan.installment_count)}
                                </p>
                            </div>
                        </div>
                    </section>
                )}

                <section>
                    <SectionTitle>{t('اقساط')}</SectionTitle>
                    {!installments ? (
                        <ListSkeleton rows={6} />
                    ) : (
                        <ListCard>
                            {installments.map((installment, index) => {
                                const status = installmentStatuses[installment.status];
                                const payable = installment.status === 'pending' || installment.status === 'overdue';

                                return (
                                    <ListRow
                                        key={installment.id}
                                        media={
                                            <span className={cn('flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold tabular-nums', installment.status === 'paid' ? 'bg-income/12 text-income' : 'bg-muted')}>
                                                {installment.status === 'paid' ? <CheckIcon className="size-5" /> : format.number(index + 1)}
                                            </span>
                                        }
                                        title={format.date(installment.due_date)}
                                        subtitle={installment.paid_at ? t('پرداخت در :date', { date: format.date(installment.paid_at) }) : undefined}
                                        trailing={
                                            <>
                                                <Money amount={installment.amount} className="text-sm font-semibold" />
                                                <span className="mt-1">
                                                    <StatusBadge label={status.label} tone={status.tone} />
                                                </span>
                                            </>
                                        }
                                        onClick={payable ? () => setPaying(installment) : undefined}
                                    />
                                );
                            })}
                        </ListCard>
                    )}
                </section>
            </PageBody>
            {loan && <PaySheet loanId={loan.id} installment={paying} accounts={accounts ?? []} onClose={() => setPaying(null)} />}
        </>
    );
}
