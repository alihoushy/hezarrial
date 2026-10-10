import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AmountInput } from '@/components/amount-input';
import { BankField } from '@/components/bank-field';
import { FormField, SelectField, SubmitButton } from '@/components/form-field';
import { PageBody, PageHeader } from '@/components/page-header';
import { ConfirmAction } from '@/components/confirm-action';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { toLatinDigits } from '@/lib/format';
import { accountTypes } from '@/lib/labels';
import type { Account, AccountType, BankOption } from '@/types';
import { t } from '@/lib/i18n';

export default function AccountForm({ account, banks }: { account?: Account | null; banks?: BankOption[] }) {
    const editing = Boolean(account);
    const form = useForm({
        name: account?.name ?? '',
        type: (account?.type ?? 'bank') as AccountType,
        bank: account?.bank ?? '',
        bank_name: account?.bank_name ?? '',
        card_last_four: account?.card_last_four ?? '',
        opening_balance: account ? String(Math.round(account.opening_balance)) : '0',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (account) {
            form.put(route('accounts.update', account.id));
        } else {
            form.post(route('accounts.store'));
        }
    };

    return (
        <>
            <Head title={editing ? t('ویرایش حساب') : t('حساب جدید')} />
            <PageHeader
                title={editing ? t('ویرایش حساب') : t('حساب جدید')}
                back={account ? route('accounts.show', account.id) : route('accounts.index')}
                backComponent={account ? 'accounts/show' : 'accounts/index'}
            />
            <PageBody className="pt-5">
                <form onSubmit={submit} noValidate>
                    <FieldGroup className="gap-6">
                        <FormField label={t('نام حساب')} htmlFor="name" error={form.errors.name}>
                            <Input
                                id="name"
                                autoFocus={!editing}
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                placeholder={t('مثلاً: بانک ملت')}
                                aria-invalid={form.errors.name ? true : undefined}
                            />
                        </FormField>

                        <FormField label={t('نوع')} htmlFor="type" error={form.errors.type}>
                            <SelectField
                                id="type"
                                value={form.data.type}
                                onChange={(event) => form.setData('type', event.target.value as AccountType)}
                                options={Object.entries(accountTypes).map(([value, { label }]) => ({ value, label: t(label) }))}
                            />
                        </FormField>

                        <FormField label={t('بانک')} htmlFor="bank" optional error={form.errors.bank ?? form.errors.bank_name}>
                            <BankField
                                id="bank"
                                banks={banks}
                                value={form.data.bank}
                                onChange={(bank) => form.setData((data) => ({ ...data, bank, bank_name: bank ? '' : data.bank_name }))}
                                fallbackLabel={account?.bank_label}
                                otherName={form.data.bank_name}
                                onOtherNameChange={(name) => form.setData('bank_name', name)}
                                aria-invalid={form.errors.bank || form.errors.bank_name ? true : undefined}
                            />
                        </FormField>

                        <FormField label={t('۴ رقم آخر کارت')} htmlFor="card_last_four" optional error={form.errors.card_last_four} description={t('فقط چهار رقم آخر ذخیره می‌شود؛ هرگز شماره کامل کارت یا رمز را وارد نکنید.')}>
                            <Input
                                id="card_last_four"
                                inputMode="numeric"
                                maxLength={4}
                                dir="ltr"
                                className="text-start tracking-widest"
                                value={form.data.card_last_four}
                                onChange={(event) => form.setData('card_last_four', toLatinDigits(event.target.value).replace(/\D/g, ''))}
                                aria-invalid={form.errors.card_last_four ? true : undefined}
                            />
                        </FormField>

                        <FormField label={t('مانده افتتاحیه')} htmlFor="opening_balance" error={form.errors.opening_balance} description={t('موجودی حساب در لحظه شروع استفاده از برنامه')}>
                            <AmountInput
                                id="opening_balance"
                                allowNegative
                                value={form.data.opening_balance}
                                onValueChange={(value) => form.setData('opening_balance', value)}
                                aria-invalid={form.errors.opening_balance ? true : undefined}
                            />
                        </FormField>

                        <div className="flex flex-col gap-2.5 pt-2">
                            <SubmitButton size="lg" processing={form.processing}>
                                {t('ذخیره')}
                            </SubmitButton>
                            {account && (
                                <ConfirmAction
                                    title={t('بایگانی حساب؟')}
                                    description={t('حساب از فهرست حذف می‌شود ولی تراکنش‌های آن در گزارش‌ها باقی می‌ماند.')}
                                    confirmLabel={t('بایگانی')}
                                    href={route('accounts.destroy', account.id)}
                                    method="delete"
                                    destructive
                                >
                                    <Button type="button" size="lg" variant="destructive">
                                        {t('بایگانی حساب')}
                                    </Button>
                                </ConfirmAction>
                            )}
                        </div>
                    </FieldGroup>
                </form>
            </PageBody>
        </>
    );
}
