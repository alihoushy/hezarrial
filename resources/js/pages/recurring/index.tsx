import { Head, router, useForm } from '@inertiajs/react';
import { PlusIcon, RepeatIcon } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { AmountInput } from '@/components/amount-input';
import { EmptyState } from '@/components/empty-state';
import { DateInput, FormField, SelectField, SubmitButton } from '@/components/form-field';
import { ListCard, ListRow } from '@/components/list';
import { Money } from '@/components/money';
import { PageBody, PageHeader } from '@/components/page-header';
import { ResponsiveModal } from '@/components/responsive-modal';
import { ListSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { todayIso, useFormat } from '@/lib/format';
import { frequencies, recurringTypes } from '@/lib/labels';
import type { CategoryOption, Option, RecurringItem } from '@/types';

interface Props {
    items?: RecurringItem[];
    accounts?: Option[];
    categories?: CategoryOption[];
}

function NewRecurringSheet({ open, onOpenChange, accounts, categories }: { open: boolean; onOpenChange: (open: boolean) => void; accounts: Option[]; categories: CategoryOption[] }) {
    const form = useForm({ title: '', type: 'expense', account_id: String(accounts[0]?.id ?? ''), category_id: '', amount: '', frequency: 'monthly', next_run_date: todayIso(), description: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('recurring.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <ResponsiveModal open={open} onOpenChange={onOpenChange} title="تراکنش تکرارشونده" description="در سررسید، یک یادآوری پیشنهادی ساخته می‌شود.">
            {accounts.length === 0 ? (
                <p className="py-6 text-center text-sm text-muted-foreground">ابتدا یک حساب بسازید.</p>
            ) : (
                <form onSubmit={submit} noValidate className="pb-4">
                    <FieldGroup className="gap-5">
                        <FormField label="عنوان" htmlFor="rec_title" error={form.errors.title}>
                            <Input id="rec_title" value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} placeholder="مثلاً: اجاره خانه" aria-invalid={form.errors.title ? true : undefined} />
                        </FormField>
                        <FormField label="نوع" htmlFor="rec_type" error={form.errors.type}>
                            <SelectField id="rec_type" value={form.data.type} onChange={(event) => form.setData('type', event.target.value)} options={Object.entries(recurringTypes).map(([value, label]) => ({ value, label }))} />
                        </FormField>
                        <FormField label="مبلغ" htmlFor="rec_amount" error={form.errors.amount}>
                            <AmountInput id="rec_amount" value={form.data.amount} onValueChange={(value) => form.setData('amount', value)} aria-invalid={form.errors.amount ? true : undefined} />
                        </FormField>
                        <FormField label="حساب" htmlFor="rec_account" error={form.errors.account_id}>
                            <SelectField id="rec_account" value={form.data.account_id} onChange={(event) => form.setData('account_id', event.target.value)} options={accounts.map((account) => ({ value: account.id, label: account.name }))} />
                        </FormField>
                        <FormField label="دسته‌بندی" htmlFor="rec_category" optional error={form.errors.category_id}>
                            <SelectField id="rec_category" value={form.data.category_id} onChange={(event) => form.setData('category_id', event.target.value)} placeholder="بدون دسته" options={categories.map((category) => ({ value: category.id, label: category.name }))} />
                        </FormField>
                        <FormField label="تکرار" htmlFor="rec_frequency" error={form.errors.frequency}>
                            <SelectField id="rec_frequency" value={form.data.frequency} onChange={(event) => form.setData('frequency', event.target.value)} options={Object.entries(frequencies).map(([value, label]) => ({ value, label }))} />
                        </FormField>
                        <FormField label="تاریخ اولین اجرا" htmlFor="rec_next" error={form.errors.next_run_date}>
                            <DateInput id="rec_next" value={form.data.next_run_date} onChange={(event) => form.setData('next_run_date', event.target.value)} />
                        </FormField>
                        <FormField label="توضیح" htmlFor="rec_description" optional error={form.errors.description}>
                            <Textarea id="rec_description" rows={2} value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} />
                        </FormField>
                        <SubmitButton size="lg" processing={form.processing}>
                            ثبت
                        </SubmitButton>
                    </FieldGroup>
                </form>
            )}
        </ResponsiveModal>
    );
}

export default function RecurringIndex({ items, accounts, categories }: Props) {
    const format = useFormat();
    const [creating, setCreating] = useState(false);

    return (
        <>
            <Head title="تکرارشونده‌ها" />
            <PageHeader
                title="تکرارشونده‌ها"
                back={route('settings.index')}
                backComponent="settings/index"
                actions={
                    <Button size="icon" variant="ghost" className="rounded-full" onClick={() => setCreating(true)} aria-label="ثبت تکرارشونده">
                        <PlusIcon className="size-6" />
                    </Button>
                }
            />
            <PageBody>
                {!items ? (
                    <ListSkeleton rows={4} />
                ) : items.length === 0 ? (
                    <EmptyState icon={RepeatIcon} title="تراکنش تکرارشونده‌ای نیست" description="اجاره، اشتراک‌ها و اقساط ثابت را یک بار ثبت کنید." action={<Button onClick={() => setCreating(true)}>ثبت تکرارشونده</Button>} />
                ) : (
                    <ListCard>
                        {items.map((item) => (
                            <ListRow
                                key={item.id}
                                icon={RepeatIcon}
                                iconClassName={item.is_active ? 'bg-brand/12 text-brand' : undefined}
                                title={item.title}
                                subtitle={
                                    <span className="flex flex-wrap items-center gap-x-2">
                                        <span>{frequencies[item.frequency] ?? item.frequency}</span>
                                        <span>· اجرای بعدی {format.shortDate(item.next_run_date)}</span>
                                    </span>
                                }
                                trailing={
                                    <>
                                        <Money amount={item.amount} className="text-sm font-semibold" />
                                        <Switch
                                            className="mt-1.5"
                                            checked={item.is_active}
                                            aria-label={item.is_active ? 'توقف' : 'فعال‌سازی'}
                                            onCheckedChange={(checked) => router.patch(route('recurring.update', item.id), { is_active: checked }, { preserveScroll: true })}
                                        />
                                    </>
                                }
                            />
                        ))}
                    </ListCard>
                )}
            </PageBody>
            <NewRecurringSheet open={creating} onOpenChange={setCreating} accounts={accounts ?? []} categories={categories ?? []} />
        </>
    );
}
