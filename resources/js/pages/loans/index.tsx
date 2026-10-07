import { Head, Link, useForm } from '@inertiajs/react';
import { LandmarkIcon, PlusIcon } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { AmountInput } from '@/components/amount-input';
import { EmptyState } from '@/components/empty-state';
import { DateInput, FormField, SelectField, SubmitButton } from '@/components/form-field';
import { ListCard, StatusBadge } from '@/components/list';
import { Money } from '@/components/money';
import { PageBody, PageHeader } from '@/components/page-header';
import { ResponsiveModal } from '@/components/responsive-modal';
import { ListSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { toLatinDigits, todayIso, useFormat } from '@/lib/format';
import { loanStatuses } from '@/lib/labels';
import type { Loan, Option } from '@/types';
import { t } from '@/lib/i18n';

function NewLoanSheet({ open, onOpenChange, accounts }: { open: boolean; onOpenChange: (open: boolean) => void; accounts: Option[] }) {
    const form = useForm({
        title: '',
        lender_name: '',
        account_id: String(accounts[0]?.id ?? ''),
        principal_amount: '',
        total_payable_amount: '',
        installment_amount: '',
        installment_count: '',
        start_date: todayIso(),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, installment_count: toLatinDigits(data.installment_count) }));
        form.post(route('loans.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <ResponsiveModal open={open} onOpenChange={onOpenChange} title={t('ثبت وام')} description={t('اقساط ماهانه از تاریخ شروع ساخته می‌شود.')}>
            {accounts.length === 0 ? (
                <p className="py-6 text-center text-sm text-muted-foreground">{t('ابتدا یک حساب بسازید.')}</p>
            ) : (
                <form onSubmit={submit} noValidate className="pb-4">
                    <FieldGroup className="gap-5">
                        <FormField label={t('عنوان وام')} htmlFor="loan_title" error={form.errors.title}>
                            <Input id="loan_title" value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} placeholder={t('مثلاً: وام خرید خودرو')} aria-invalid={form.errors.title ? true : undefined} />
                        </FormField>
                        <FormField label={t('وام‌دهنده')} htmlFor="loan_lender" optional error={form.errors.lender_name}>
                            <Input id="loan_lender" value={form.data.lender_name} onChange={(event) => form.setData('lender_name', event.target.value)} />
                        </FormField>
                        <FormField label={t('حساب دریافت وام')} htmlFor="loan_account" error={form.errors.account_id}>
                            <SelectField id="loan_account" value={form.data.account_id} onChange={(event) => form.setData('account_id', event.target.value)} options={accounts.map((account) => ({ value: account.id, label: account.name }))} />
                        </FormField>
                        <FormField label={t('اصل وام')} htmlFor="loan_principal" error={form.errors.principal_amount}>
                            <AmountInput id="loan_principal" value={form.data.principal_amount} onValueChange={(value) => form.setData('principal_amount', value)} aria-invalid={form.errors.principal_amount ? true : undefined} />
                        </FormField>
                        <FormField label={t('مبلغ کل قابل پرداخت')} htmlFor="loan_total" error={form.errors.total_payable_amount} description={t('اصل وام به‌علاوه سود')}>
                            <AmountInput id="loan_total" value={form.data.total_payable_amount} onValueChange={(value) => form.setData('total_payable_amount', value)} aria-invalid={form.errors.total_payable_amount ? true : undefined} />
                        </FormField>
                        <FormField label={t('مبلغ هر قسط')} htmlFor="loan_installment" error={form.errors.installment_amount}>
                            <AmountInput id="loan_installment" value={form.data.installment_amount} onValueChange={(value) => form.setData('installment_amount', value)} aria-invalid={form.errors.installment_amount ? true : undefined} />
                        </FormField>
                        <FormField label={t('تعداد اقساط')} htmlFor="loan_count" error={form.errors.installment_count}>
                            <Input
                                id="loan_count"
                                inputMode="numeric"
                                dir="ltr"
                                className="text-start"
                                value={form.data.installment_count}
                                onChange={(event) => form.setData('installment_count', toLatinDigits(event.target.value).replace(/\D/g, ''))}
                                aria-invalid={form.errors.installment_count ? true : undefined}
                            />
                        </FormField>
                        <FormField label={t('تاریخ اولین قسط')} htmlFor="loan_start" error={form.errors.start_date}>
                            <DateInput id="loan_start" value={form.data.start_date} onChange={(event) => form.setData('start_date', event.target.value)} />
                        </FormField>
                        <SubmitButton size="lg" processing={form.processing}>
                            {t('ثبت وام')}
                        </SubmitButton>
                    </FieldGroup>
                </form>
            )}
        </ResponsiveModal>
    );
}

export default function LoansIndex({ loans, accounts }: { loans?: Loan[]; accounts?: Option[] }) {
    const format = useFormat();
    const [creating, setCreating] = useState(false);

    return (
        <>
            <Head title={t('وام و اقساط')} />
            <PageHeader
                title={t('وام و اقساط')}
                back={route('settings.index')}
                backComponent="settings/index"
                actions={
                    <Button size="icon" variant="ghost" className="rounded-full" onClick={() => setCreating(true)} aria-label={t('ثبت وام')}>
                        <PlusIcon className="size-6" />
                    </Button>
                }
            />
            <PageBody>
                {!loans ? (
                    <ListSkeleton rows={4} />
                ) : loans.length === 0 ? (
                    <EmptyState icon={LandmarkIcon} title={t('وامی ثبت نشده')} description={t('وام‌ها و برنامه اقساط خود را اینجا دنبال کنید.')} action={<Button onClick={() => setCreating(true)}>{t('ثبت وام')}</Button>} />
                ) : (
                    <ListCard>
                        {loans.map((loan) => {
                            const status = loanStatuses[loan.status];
                            const percent = loan.installment_count ? (loan.paid_installment_count / loan.installment_count) * 100 : 0;

                            return (
                                <Link key={loan.id} href={route('loans.show', loan.id)} component="loans/show" pageProps={{ loan }} className="flex flex-col gap-3 border-b border-border/60 px-4 py-4 last:border-b-0 active:bg-muted/70">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="truncate font-semibold">{loan.title}</p>
                                            <p className="text-xs text-muted-foreground">{loan.lender_name || t('شروع :date', { date: format.shortDate(loan.start_date) })}</p>
                                        </div>
                                        <StatusBadge label={status.label} tone={status.tone} />
                                    </div>
                                    <Progress value={percent} aria-label={t('پیشرفت پرداخت')} />
                                    <div className="flex items-center justify-between text-xs text-muted-foreground">
                                        <span>
                                            {t(':paid از :total قسط', { paid: format.number(loan.paid_installment_count), total: format.number(loan.installment_count) })}
                                        </span>
                                        <Money amount={loan.installment_amount} className="text-sm font-semibold text-foreground" />
                                    </div>
                                </Link>
                            );
                        })}
                    </ListCard>
                )}
            </PageBody>
            <NewLoanSheet open={creating} onOpenChange={setCreating} accounts={accounts ?? []} />
        </>
    );
}
