import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { LandmarkIcon } from 'lucide-react';
import type { FormEvent } from 'react';
import { AmountInput } from '@/components/amount-input';
import { EmptyState } from '@/components/empty-state';
import { DateInput, FormField, SelectField, SubmitButton } from '@/components/form-field';
import { PageBody, PageHeader } from '@/components/page-header';
import { FormSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { todayIso } from '@/lib/format';
import { creatableTransactionTypes, transactionTypes } from '@/lib/labels';
import { cn } from '@/lib/utils';
import type { CategoryOption, Option, PersonOption, TransactionType } from '@/types';
import { t } from '@/lib/i18n';

interface TransactionFormData {
    id: number;
    type: TransactionType;
    amount: number;
    account_id: number;
    category_id: number | null;
    person_id: number | null;
    transaction_date: string | null;
    description: string | null;
}

interface Props {
    transaction?: TransactionFormData | null;
    initialType?: string | null;
    accounts?: Option[];
    categories?: CategoryOption[];
    people?: PersonOption[];
}

const TYPES_WITH_PERSON: TransactionType[] = ['debt_payment', 'receivable_collection', 'check_payment', 'check_receive', 'income', 'expense', 'loan_installment_payment'];

const INCOMING_TYPES: TransactionType[] = ['income', 'transfer_in', 'debt_received', 'receivable_collection', 'loan_receive', 'check_receive'];

/** Money coming in is filed under income categories, money going out under expense ones. */
function categoryTypeFor(type: TransactionType): 'income' | 'expense' {
    return INCOMING_TYPES.includes(type) ? 'income' : 'expense';
}

function TransactionForm({ transaction, initialType, accounts, categories, people }: Required<Omit<Props, 'transaction' | 'initialType'>> & Pick<Props, 'transaction' | 'initialType'>) {
    const editing = Boolean(transaction);
    const startType = (transaction?.type ?? (initialType && initialType in transactionTypes ? initialType : 'expense')) as TransactionType;

    const form = useForm({
        type: startType,
        amount: transaction ? String(Math.round(transaction.amount)) : '',
        account_id: String(transaction?.account_id ?? accounts[0]?.id ?? ''),
        destination_account_id: '',
        category_id: transaction?.category_id ? String(transaction.category_id) : '',
        person_id: transaction?.person_id ? String(transaction.person_id) : '',
        transaction_date: transaction?.transaction_date ?? todayIso(),
        description: transaction?.description ?? '',
    });

    const type = form.data.type;
    const isTransfer = type === 'transfer_out';
    const needsCategory = type === 'income' || type === 'expense';
    const showCategory = !isTransfer && type !== 'adjustment';
    const typeOptions = creatableTransactionTypes.includes(type) ? creatableTransactionTypes : [type, ...creatableTransactionTypes];
    const categoryOptions = categories.filter((category) => category.type === categoryTypeFor(type));

    const changeType = (next: TransactionType) => {
        const stillValid = categories.some((category) => String(category.id) === form.data.category_id && category.type === categoryTypeFor(next));
        form.setData((data) => ({ ...data, type: next, category_id: stillValid ? data.category_id : '' }));
    };

    const submit = (event: FormEvent, addAnother = false) => {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            category_id: showCategory ? data.category_id : '',
            destination_account_id: isTransfer ? data.destination_account_id : '',
            ...(addAnother ? { save_add_another: 1 } : {}),
        }));

        if (editing && transaction) {
            form.put(route('transactions.update', transaction.id));
        } else {
            form.post(route('transactions.store'), {
                onSuccess: () => {
                    if (addAnother) {
                        form.reset('amount', 'description');
                    }
                },
            });
        }
    };

    if (accounts.length === 0) {
        return (
            <EmptyState
                icon={LandmarkIcon}
                title={t('ابتدا یک حساب بسازید')}
                description={t('هر تراکنش به یک حساب (بانک، کارت یا نقد) تعلق دارد.')}
                action={
                    <Button asChild>
                        <Link href={route('accounts.create')} component="accounts/form">
                            {t('افزودن حساب')}
                        </Link>
                    </Button>
                }
            />
        );
    }

    return (
        <form onSubmit={(event) => submit(event)} noValidate>
            <FieldGroup className="gap-6">
                <div className="-mx-4">
                    <ToggleGroup
                        type="single"
                        value={type}
                        onValueChange={(value) => value && changeType(value as TransactionType)}
                        className="no-scrollbar w-full justify-start gap-2 overflow-x-auto px-4"
                        aria-label={t('نوع تراکنش')}
                    >
                        {typeOptions.map((option) => {
                            const Icon = transactionTypes[option].icon;

                            return (
                                <ToggleGroupItem
                                    key={option}
                                    value={option}
                                    className="h-10 shrink-0 rounded-full border border-border bg-card px-4 data-[state=on]:border-primary data-[state=on]:bg-primary data-[state=on]:text-primary-foreground"
                                >
                                    <Icon className="size-4" />
                                    {t(transactionTypes[option].label)}
                                </ToggleGroupItem>
                            );
                        })}
                    </ToggleGroup>
                    {form.errors.type && <p className="px-4 pt-2 text-xs text-destructive">{form.errors.type}</p>}
                </div>

                <FormField label={t('مبلغ')} htmlFor="amount" error={form.errors.amount}>
                    <AmountInput
                        id="amount"
                        size="lg"
                        autoFocus={!editing}
                        value={form.data.amount}
                        onValueChange={(value) => form.setData('amount', value)}
                        aria-invalid={form.errors.amount ? true : undefined}
                    />
                </FormField>

                <FormField label={isTransfer ? t('از حساب') : t('حساب')} htmlFor="account_id" error={form.errors.account_id}>
                    <SelectField
                        id="account_id"
                        value={form.data.account_id}
                        onChange={(event) => form.setData('account_id', event.target.value)}
                        options={accounts.map((account) => ({ value: account.id, label: account.name }))}
                        aria-invalid={form.errors.account_id ? true : undefined}
                    />
                </FormField>

                {isTransfer && (
                    <FormField label={t('به حساب')} htmlFor="destination_account_id" error={form.errors.destination_account_id}>
                        <SelectField
                            id="destination_account_id"
                            value={form.data.destination_account_id}
                            onChange={(event) => form.setData('destination_account_id', event.target.value)}
                            placeholder={t('انتخاب حساب مقصد')}
                            options={accounts.filter((account) => String(account.id) !== form.data.account_id).map((account) => ({ value: account.id, label: account.name }))}
                            aria-invalid={form.errors.destination_account_id ? true : undefined}
                        />
                    </FormField>
                )}

                {showCategory && (
                    <FormField label={t('دسته‌بندی')} optional={!needsCategory} error={form.errors.category_id}>
                        {categoryOptions.length > 0 ? (
                            <ToggleGroup
                                type="single"
                                value={form.data.category_id}
                                onValueChange={(value) => form.setData('category_id', value)}
                                className="w-full flex-wrap justify-start gap-2"
                                aria-label={t('دسته‌بندی')}
                            >
                                {categoryOptions.map((category) => (
                                    <ToggleGroupItem
                                        key={category.id}
                                        value={String(category.id)}
                                        className={cn(
                                            'h-9 rounded-full border border-border bg-card px-3.5 text-[0.85rem] font-normal',
                                            'data-[state=on]:border-primary data-[state=on]:bg-primary/5 data-[state=on]:font-medium data-[state=on]:text-foreground',
                                        )}
                                    >
                                        <span className="size-2 rounded-full" style={{ background: category.color || 'var(--muted-foreground)' }} />
                                        {category.name}
                                    </ToggleGroupItem>
                                ))}
                            </ToggleGroup>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                {t(categoryTypeFor(type) === 'income' ? 'دسته‌بندی درآمد ندارید.' : 'دسته‌بندی هزینه ندارید.')}{' '}
                                <Link href={route('categories.create')} component="categories/form" className="font-medium text-brand">
                                    {t('ساخت دسته‌بندی')}
                                </Link>
                            </p>
                        )}
                    </FormField>
                )}

                {TYPES_WITH_PERSON.includes(type) && people.length > 0 && (
                    <FormField label={t('شخص')} htmlFor="person_id" optional error={form.errors.person_id}>
                        <SelectField
                            id="person_id"
                            value={form.data.person_id}
                            onChange={(event) => form.setData('person_id', event.target.value)}
                            placeholder={t('بدون شخص')}
                            options={people.map((person) => ({ value: person.id, label: person.full_name }))}
                        />
                    </FormField>
                )}

                <FormField label={t('تاریخ')} htmlFor="transaction_date" error={form.errors.transaction_date}>
                    <DateInput
                        id="transaction_date"
                        value={form.data.transaction_date}
                        onChange={(event) => form.setData('transaction_date', event.target.value)}
                        aria-invalid={form.errors.transaction_date ? true : undefined}
                    />
                </FormField>

                <FormField label={type === 'adjustment' ? t('دلیل اصلاح') : t('شرح')} htmlFor="description" optional={type !== 'adjustment'} error={form.errors.description}>
                    <Textarea
                        id="description"
                        value={form.data.description}
                        onChange={(event) => form.setData('description', event.target.value)}
                        rows={2}
                        placeholder={type === 'adjustment' ? t('مثلاً: مغایرت با صورت‌حساب بانک') : t('مثلاً: خرید هفتگی')}
                        aria-invalid={form.errors.description ? true : undefined}
                    />
                </FormField>

                <div className="flex flex-col gap-2.5 pt-2">
                    <SubmitButton size="lg" processing={form.processing}>
                        {editing ? t('ذخیره تغییرات') : t('ذخیره')}
                    </SubmitButton>
                    {!editing && (
                        <Button type="button" size="lg" variant="outline" disabled={form.processing} onClick={(event) => submit(event, true)}>
                            {t('ذخیره و ثبت بعدی')}
                        </Button>
                    )}
                </div>
            </FieldGroup>
        </form>
    );
}

export default function TransactionFormPage({ transaction, initialType, accounts, categories, people }: Props) {
    const { url } = usePage();
    const editing = transaction ? true : url.includes('/edit');
    const loaded = accounts !== undefined && categories !== undefined && people !== undefined;
    const back = editing && transaction ? route('transactions.show', transaction.id) : route('transactions.index');

    return (
        <>
            <Head title={editing ? t('ویرایش تراکنش') : t('تراکنش جدید')} />
            <PageHeader title={editing ? t('ویرایش تراکنش') : t('تراکنش جدید')} back={back} backComponent={editing ? 'transactions/show' : 'transactions/index'} />
            <PageBody className="pt-5">
                {loaded ? (
                    <TransactionForm transaction={transaction} initialType={initialType} accounts={accounts} categories={categories} people={people} />
                ) : (
                    <FormSkeleton fields={5} />
                )}
            </PageBody>
        </>
    );
}
