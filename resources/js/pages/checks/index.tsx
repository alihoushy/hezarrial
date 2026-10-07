import { Head, useForm } from '@inertiajs/react';
import { PlusIcon, ReceiptTextIcon } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import { AmountInput } from '@/components/amount-input';
import { ConfirmAction } from '@/components/confirm-action';
import { EmptyState } from '@/components/empty-state';
import { DateInput, FormField, SelectField, SubmitButton } from '@/components/form-field';
import { ListCard, ListRow, StatusBadge } from '@/components/list';
import { Money } from '@/components/money';
import { PageBody, PageHeader } from '@/components/page-header';
import { ResponsiveModal } from '@/components/responsive-modal';
import { ListSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { todayIso, toLatinDigits, useFormat } from '@/lib/format';
import { checkStatuses } from '@/lib/labels';
import type { Check, Option, PersonOption } from '@/types';
import { t } from '@/lib/i18n';

interface Props {
    checks?: Check[];
    accounts?: Option[];
    people?: PersonOption[];
}

function NewCheckSheet({ open, onOpenChange, accounts, people }: { open: boolean; onOpenChange: (open: boolean) => void; accounts: Option[]; people: PersonOption[] }) {
    const form = useForm({ type: 'payable', amount: '', due_date: todayIso(), check_number: '', bank_name: '', account_id: '', person_id: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, check_number: toLatinDigits(data.check_number) }));
        form.post(route('checks.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <ResponsiveModal open={open} onOpenChange={onOpenChange} title={t('ثبت چک')}>
            <form onSubmit={submit} noValidate className="pb-4">
                <FieldGroup className="gap-5">
                    <ToggleGroup type="single" value={form.data.type} onValueChange={(value) => value && form.setData('type', value)} className="grid w-full grid-cols-2 gap-2" aria-label={t('نوع چک')}>
                        <ToggleGroupItem value="payable" className="h-11 rounded-xl border border-border data-[state=on]:border-expense data-[state=on]:bg-expense/10 data-[state=on]:text-expense">
                            {t('پرداختنی')}
                        </ToggleGroupItem>
                        <ToggleGroupItem value="receivable" className="h-11 rounded-xl border border-border data-[state=on]:border-income data-[state=on]:bg-income/10 data-[state=on]:text-income">
                            {t('دریافتنی')}
                        </ToggleGroupItem>
                    </ToggleGroup>
                    <FormField label={t('مبلغ')} htmlFor="check_amount" error={form.errors.amount}>
                        <AmountInput id="check_amount" value={form.data.amount} onValueChange={(value) => form.setData('amount', value)} aria-invalid={form.errors.amount ? true : undefined} />
                    </FormField>
                    <FormField label={t('تاریخ سررسید')} htmlFor="check_due" error={form.errors.due_date}>
                        <DateInput id="check_due" value={form.data.due_date} onChange={(event) => form.setData('due_date', event.target.value)} />
                    </FormField>
                    <FormField label={t('شماره چک')} htmlFor="check_number" optional error={form.errors.check_number}>
                        <Input id="check_number" inputMode="numeric" dir="ltr" className="text-start" value={form.data.check_number} onChange={(event) => form.setData('check_number', event.target.value)} />
                    </FormField>
                    <FormField label={t('نام بانک')} htmlFor="check_bank" optional error={form.errors.bank_name}>
                        <Input id="check_bank" value={form.data.bank_name} onChange={(event) => form.setData('bank_name', event.target.value)} />
                    </FormField>
                    <FormField label={t('حساب')} htmlFor="check_account" optional error={form.errors.account_id}>
                        <SelectField id="check_account" value={form.data.account_id} onChange={(event) => form.setData('account_id', event.target.value)} placeholder={t('بعداً انتخاب می‌شود')} options={accounts.map((account) => ({ value: account.id, label: account.name }))} />
                    </FormField>
                    {people.length > 0 && (
                        <FormField label={t('شخص')} htmlFor="check_person" optional error={form.errors.person_id}>
                            <SelectField id="check_person" value={form.data.person_id} onChange={(event) => form.setData('person_id', event.target.value)} placeholder={t('بدون شخص')} options={people.map((person) => ({ value: person.id, label: person.full_name }))} />
                        </FormField>
                    )}
                    <SubmitButton size="lg" processing={form.processing}>
                        {t('ثبت چک')}
                    </SubmitButton>
                </FieldGroup>
            </form>
        </ResponsiveModal>
    );
}

function CheckActionsSheet({ check, accounts, onClose }: { check: Check | null; accounts: Option[]; onClose: () => void }) {
    const form = useForm({ account_id: '' });
    const format = useFormat();
    const checkId = check?.id;
    const defaultAccount = check?.account?.id ?? accounts[0]?.id;

    useEffect(() => {
        form.setData('account_id', String(defaultAccount ?? ''));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [checkId, defaultAccount]);

    const pass = (event: FormEvent) => {
        event.preventDefault();

        if (check) {
            form.post(route('checks.pass', check.id), { preserveScroll: true, onSuccess: onClose });
        }
    };

    return (
        <ResponsiveModal
            open={check !== null}
            onOpenChange={(next) => !next && onClose()}
            title={check ? (check.check_number ? t('چک :number', { number: format.digits(check.check_number) }) : t('چک')) : t('چک')}
            description={check ? t(':amount :unit · سررسید :date', { amount: format.money(check.amount), unit: format.unit, date: format.date(check.due_date) }) : undefined}
        >
            {check && (
                <div className="flex flex-col gap-5 pb-4">
                    <form onSubmit={pass} noValidate>
                        <FieldGroup className="gap-4">
                            <FormField label={check.type === 'payable' ? t('پرداخت از حساب') : t('واریز به حساب')} htmlFor="pass_account" error={form.errors.account_id}>
                                <SelectField id="pass_account" value={form.data.account_id} onChange={(event) => form.setData('account_id', event.target.value)} options={accounts.map((account) => ({ value: account.id, label: account.name }))} />
                            </FormField>
                            <SubmitButton size="lg" processing={form.processing}>
                                {t('پاس شد')}
                            </SubmitButton>
                        </FieldGroup>
                    </form>
                    <div className="grid grid-cols-2 gap-2.5">
                        <ConfirmAction title={t('چک برگشت خورد؟')} description={t('وضعیت چک برگشتی ثبت می‌شود و مانده حساب‌ها تغییر نمی‌کند.')} confirmLabel={t('ثبت برگشتی')} href={route('checks.bounce', check.id)} destructive onSuccess={onClose}>
                            <Button type="button" variant="outline" size="lg">
                                {t('برگشتی')}
                            </Button>
                        </ConfirmAction>
                        <ConfirmAction title={t('چک باطل شود؟')} confirmLabel={t('باطل شود')} href={route('checks.cancel', check.id)} destructive onSuccess={onClose}>
                            <Button type="button" variant="outline" size="lg">
                                {t('باطل')}
                            </Button>
                        </ConfirmAction>
                    </div>
                </div>
            )}
        </ResponsiveModal>
    );
}

export default function ChecksIndex({ checks, accounts, people }: Props) {
    const format = useFormat();
    const [filter, setFilter] = useState('pending');
    const [creating, setCreating] = useState(false);
    const [selected, setSelected] = useState<Check | null>(null);
    const visible = (checks ?? []).filter((check) => (filter === 'pending' ? check.status === 'pending' : filter === 'done' ? check.status !== 'pending' : true));

    return (
        <>
            <Head title={t('چک‌ها')} />
            <PageHeader
                title={t('چک‌ها')}
                back={route('settings.index')}
                backComponent="settings/index"
                actions={
                    <Button size="icon" variant="ghost" className="rounded-full" onClick={() => setCreating(true)} aria-label={t('ثبت چک')}>
                        <PlusIcon className="size-6" />
                    </Button>
                }
            />
            <PageBody>
                <ToggleGroup type="single" value={filter} onValueChange={(value) => value && setFilter(value)} className="w-full gap-2" aria-label={t('فیلتر')}>
                    {[
                        ['pending', t('در انتظار')],
                        ['done', t('نهایی‌شده')],
                        ['all', t('همه')],
                    ].map(([value, label]) => (
                        <ToggleGroupItem key={value} value={value} className="h-9 flex-1 rounded-full border border-border bg-card data-[state=on]:border-primary data-[state=on]:bg-primary data-[state=on]:text-primary-foreground">
                            {label}
                        </ToggleGroupItem>
                    ))}
                </ToggleGroup>

                {!checks ? (
                    <ListSkeleton rows={5} />
                ) : visible.length === 0 ? (
                    <EmptyState icon={ReceiptTextIcon} title={t('چکی نیست')} description={t('چک‌های پرداختنی و دریافتنی شما اینجا نمایش داده می‌شود.')} action={<Button onClick={() => setCreating(true)}>{t('ثبت چک')}</Button>} />
                ) : (
                    <ListCard>
                        {visible.map((check) => {
                            const status = checkStatuses[check.status];
                            const payable = check.type === 'payable';

                            return (
                                <ListRow
                                    key={check.id}
                                    icon={ReceiptTextIcon}
                                    iconClassName={payable ? 'bg-expense/10 text-expense' : 'bg-income/12 text-income'}
                                    title={check.check_number ? t('چک :number', { number: format.digits(check.check_number) }) : payable ? t('چک پرداختنی') : t('چک دریافتنی')}
                                    subtitle={[check.person?.name, check.bank_name, format.shortDate(check.due_date)].filter(Boolean).join(' · ')}
                                    trailing={
                                        <>
                                            <Money amount={check.amount} tone={payable ? 'expense' : 'income'} className="text-sm font-semibold" />
                                            <span className="mt-1">
                                                <StatusBadge label={status.label} tone={status.tone} />
                                            </span>
                                        </>
                                    }
                                    onClick={check.status === 'pending' ? () => setSelected(check) : undefined}
                                />
                            );
                        })}
                    </ListCard>
                )}
            </PageBody>
            <NewCheckSheet open={creating} onOpenChange={setCreating} accounts={accounts ?? []} people={people ?? []} />
            <CheckActionsSheet check={selected} accounts={accounts ?? []} onClose={() => setSelected(null)} />
        </>
    );
}
