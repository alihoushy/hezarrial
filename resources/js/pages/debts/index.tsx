import { Head, useForm } from '@inertiajs/react';
import { HandCoinsIcon, PlusIcon } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import { AmountInput } from '@/components/amount-input';
import { EmptyState } from '@/components/empty-state';
import { DateInput, FormField, SelectField, SubmitButton } from '@/components/form-field';
import { ListCard, ListRow, StatusBadge } from '@/components/list';
import { Money } from '@/components/money';
import { PageBody, PageHeader } from '@/components/page-header';
import { PersonAvatar } from '@/components/person-avatar';
import { ResponsiveModal } from '@/components/responsive-modal';
import { ListSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { Textarea } from '@/components/ui/textarea';
import { todayIso, useFormat } from '@/lib/format';
import { debtStatuses } from '@/lib/labels';
import type { Debt, Option, PersonOption } from '@/types';
import { t } from '@/lib/i18n';

interface Props {
    debts?: Debt[];
    people?: PersonOption[];
    accounts?: Option[];
}

function NewDebtSheet({ open, onOpenChange, people }: { open: boolean; onOpenChange: (open: boolean) => void; people: PersonOption[] }) {
    const form = useForm({ person_id: '', type: 'payable', original_amount: '', due_date: '', description: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('debts.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <ResponsiveModal open={open} onOpenChange={onOpenChange} title={t('ثبت طلب یا بدهی')}>
            {people.length === 0 ? (
                <p className="py-6 text-center text-sm text-muted-foreground">{t('ابتدا از بخش «اشخاص» یک شخص اضافه کنید.')}</p>
            ) : (
                <form onSubmit={submit} noValidate className="pb-4">
                    <FieldGroup className="gap-5">
                        <ToggleGroup
                            type="single"
                            value={form.data.type}
                            onValueChange={(value) => value && form.setData('type', value)}
                            className="grid w-full grid-cols-2 gap-2"
                            aria-label={t('نوع')}
                        >
                            <ToggleGroupItem value="payable" className="h-11 rounded-xl border border-border data-[state=on]:border-expense data-[state=on]:bg-expense/10 data-[state=on]:text-expense">
                                {t('بدهی من')}
                            </ToggleGroupItem>
                            <ToggleGroupItem value="receivable" className="h-11 rounded-xl border border-border data-[state=on]:border-income data-[state=on]:bg-income/10 data-[state=on]:text-income">
                                {t('طلب من')}
                            </ToggleGroupItem>
                        </ToggleGroup>
                        <FormField label={t('شخص')} htmlFor="debt_person" error={form.errors.person_id}>
                            <SelectField
                                id="debt_person"
                                value={form.data.person_id}
                                onChange={(event) => form.setData('person_id', event.target.value)}
                                placeholder={t('انتخاب شخص')}
                                options={people.map((person) => ({ value: person.id, label: person.full_name }))}
                                aria-invalid={form.errors.person_id ? true : undefined}
                            />
                        </FormField>
                        <FormField label={t('مبلغ')} htmlFor="debt_amount" error={form.errors.original_amount}>
                            <AmountInput id="debt_amount" value={form.data.original_amount} onValueChange={(value) => form.setData('original_amount', value)} aria-invalid={form.errors.original_amount ? true : undefined} />
                        </FormField>
                        <FormField label={t('سررسید')} htmlFor="debt_due" optional error={form.errors.due_date}>
                            <DateInput id="debt_due" clearable value={form.data.due_date} onChange={(event) => form.setData('due_date', event.target.value)} />
                        </FormField>
                        <FormField label={t('توضیح')} htmlFor="debt_description" optional error={form.errors.description}>
                            <Textarea id="debt_description" rows={2} value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} />
                        </FormField>
                        <SubmitButton size="lg" processing={form.processing}>
                            {t('ثبت')}
                        </SubmitButton>
                    </FieldGroup>
                </form>
            )}
        </ResponsiveModal>
    );
}

function SettleSheet({ debt, accounts, onClose }: { debt: Debt | null; accounts: Option[]; onClose: () => void }) {
    const form = useForm({ account_id: String(accounts[0]?.id ?? ''), amount: '', transaction_date: todayIso(), description: '' });
    const format = useFormat();
    const open = debt !== null;
    const payable = debt?.type === 'payable';
    const debtId = debt?.id;
    const remaining = debt?.remaining_amount;

    // Start from the full remaining balance each time a different debt opens.
    useEffect(() => {
        if (remaining !== undefined) {
            form.setData('amount', String(Math.round(remaining)));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [debtId, remaining]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (!debt) {
            return;
        }

        form.post(route('debts.settle', debt.id), {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
            },
        });
    };

    return (
        <ResponsiveModal
            open={open}
            onOpenChange={(next) => !next && onClose()}
            title={payable ? t('پرداخت بدهی') : t('دریافت طلب')}
            description={debt ? t(':person · مانده :amount :unit', { person: debt.person?.name ?? '', amount: format.money(debt.remaining_amount), unit: format.unit }) : undefined}
        >
            {debt && (
                <form onSubmit={submit} noValidate className="pb-4">
                    <FieldGroup className="gap-5">
                        <FormField label={t('مبلغ')} htmlFor="settle_amount" error={form.errors.amount}>
                            <AmountInput
                                id="settle_amount"
                                value={form.data.amount}
                                onValueChange={(value) => form.setData('amount', value)}
                                aria-invalid={form.errors.amount ? true : undefined}
                            />
                        </FormField>
                        <FormField label={payable ? t('پرداخت از حساب') : t('واریز به حساب')} htmlFor="settle_account" error={form.errors.account_id}>
                            <SelectField
                                id="settle_account"
                                value={form.data.account_id}
                                onChange={(event) => form.setData('account_id', event.target.value)}
                                options={accounts.map((account) => ({ value: account.id, label: account.name }))}
                            />
                        </FormField>
                        <FormField label={t('تاریخ')} htmlFor="settle_date" error={form.errors.transaction_date}>
                            <DateInput id="settle_date" value={form.data.transaction_date} onChange={(event) => form.setData('transaction_date', event.target.value)} />
                        </FormField>
                        <SubmitButton size="lg" processing={form.processing}>
                            {t('ثبت تسویه')}
                        </SubmitButton>
                    </FieldGroup>
                </form>
            )}
        </ResponsiveModal>
    );
}

export default function DebtsIndex({ debts, people, accounts }: Props) {
    const format = useFormat();
    const [filter, setFilter] = useState('open');
    const [creating, setCreating] = useState(false);
    const [settling, setSettling] = useState<Debt | null>(null);

    const isOpen = (debt: Debt) => debt.status !== 'settled' && debt.status !== 'cancelled';
    const visible = (debts ?? []).filter((debt) => (filter === 'open' ? isOpen(debt) : filter === 'settled' ? !isOpen(debt) : true));

    return (
        <>
            <Head title={t('طلب و بدهی')} />
            <PageHeader
                title={t('طلب و بدهی')}
                back={route('settings.index')}
                backComponent="settings/index"
                actions={
                    <Button size="icon" variant="ghost" className="rounded-full" onClick={() => setCreating(true)} aria-label={t('ثبت جدید')}>
                        <PlusIcon className="size-6" />
                    </Button>
                }
            />
            <PageBody>
                <ToggleGroup type="single" value={filter} onValueChange={(value) => value && setFilter(value)} className="w-full gap-2" aria-label={t('فیلتر')}>
                    {[
                        ['open', t('باز')],
                        ['settled', t('تسویه‌شده')],
                        ['all', t('همه')],
                    ].map(([value, label]) => (
                        <ToggleGroupItem key={value} value={value} className="h-9 flex-1 rounded-full border border-border bg-card data-[state=on]:border-primary data-[state=on]:bg-primary data-[state=on]:text-primary-foreground">
                            {label}
                        </ToggleGroupItem>
                    ))}
                </ToggleGroup>

                {!debts ? (
                    <ListSkeleton rows={5} />
                ) : visible.length === 0 ? (
                    <EmptyState
                        icon={HandCoinsIcon}
                        title={t('موردی نیست')}
                        description={t('طلب‌ها و بدهی‌های شما اینجا نمایش داده می‌شود.')}
                        action={<Button onClick={() => setCreating(true)}>{t('ثبت طلب یا بدهی')}</Button>}
                    />
                ) : (
                    <ListCard>
                        {visible.map((debt) => {
                            const payable = debt.type === 'payable';
                            const status = debtStatuses[debt.status];

                            return (
                                <ListRow
                                    key={debt.id}
                                    media={<PersonAvatar name={debt.person?.name ?? '؟'} />}
                                    title={debt.person?.name ?? t('بدون شخص')}
                                    subtitle={
                                        <span className="flex items-center gap-2">
                                            {payable ? t('بدهی من') : t('طلب من')}
                                            {debt.due_date && <span>· {t('سررسید :date', { date: format.shortDate(debt.due_date) })}</span>}
                                        </span>
                                    }
                                    trailing={
                                        <>
                                            <Money amount={debt.remaining_amount} tone={payable ? 'expense' : 'income'} className="text-sm font-semibold" />
                                            <span className="mt-1">
                                                <StatusBadge label={status.label} tone={status.tone} />
                                            </span>
                                        </>
                                    }
                                    onClick={isOpen(debt) ? () => setSettling(debt) : undefined}
                                />
                            );
                        })}
                    </ListCard>
                )}
            </PageBody>

            <NewDebtSheet open={creating} onOpenChange={setCreating} people={people ?? []} />
            <SettleSheet debt={settling} accounts={accounts ?? []} onClose={() => setSettling(null)} />
        </>
    );
}
