import { Head, useForm } from '@inertiajs/react';
import { PiggyBankIcon, PlusIcon } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { AmountInput } from '@/components/amount-input';
import { EmptyState } from '@/components/empty-state';
import { DateInput, FormField, SelectField, SubmitButton } from '@/components/form-field';
import { Money } from '@/components/money';
import { PageBody, PageHeader } from '@/components/page-header';
import { ResponsiveModal } from '@/components/responsive-modal';
import { ListSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { todayIso, useFormat } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Budget, Option } from '@/types';

function NewBudgetSheet({ open, onOpenChange, categories }: { open: boolean; onOpenChange: (open: boolean) => void; categories: Option[] }) {
    const form = useForm({ title: '', category_id: '', amount: '', start_date: todayIso(), end_date: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('budgets.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <ResponsiveModal open={open} onOpenChange={onOpenChange} title="ثبت بودجه">
            <form onSubmit={submit} noValidate className="pb-4">
                <FieldGroup className="gap-5">
                    <FormField label="عنوان بودجه" htmlFor="budget_title" error={form.errors.title}>
                        <Input id="budget_title" value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} placeholder="مثلاً: خوراک ماهانه" aria-invalid={form.errors.title ? true : undefined} />
                    </FormField>
                    <FormField label="دسته‌بندی" htmlFor="budget_category" optional error={form.errors.category_id}>
                        <SelectField id="budget_category" value={form.data.category_id} onChange={(event) => form.setData('category_id', event.target.value)} placeholder="همه هزینه‌ها" options={categories.map((category) => ({ value: category.id, label: category.name }))} />
                    </FormField>
                    <FormField label="مبلغ بودجه" htmlFor="budget_amount" error={form.errors.amount}>
                        <AmountInput id="budget_amount" value={form.data.amount} onValueChange={(value) => form.setData('amount', value)} aria-invalid={form.errors.amount ? true : undefined} />
                    </FormField>
                    <FormField label="از تاریخ" htmlFor="budget_start" error={form.errors.start_date}>
                        <DateInput id="budget_start" value={form.data.start_date} onChange={(event) => form.setData('start_date', event.target.value)} />
                    </FormField>
                    <FormField label="تا تاریخ" htmlFor="budget_end" optional error={form.errors.end_date}>
                        <DateInput id="budget_end" clearable value={form.data.end_date} onChange={(event) => form.setData('end_date', event.target.value)} />
                    </FormField>
                    <SubmitButton size="lg" processing={form.processing}>
                        ثبت بودجه
                    </SubmitButton>
                </FieldGroup>
            </form>
        </ResponsiveModal>
    );
}

export default function BudgetsIndex({ budgets, categories }: { budgets?: Budget[]; categories?: Option[] }) {
    const format = useFormat();
    const [creating, setCreating] = useState(false);

    return (
        <>
            <Head title="بودجه‌بندی" />
            <PageHeader
                title="بودجه‌بندی"
                back={route('settings.index')}
                backComponent="settings/index"
                actions={
                    <Button size="icon" variant="ghost" className="rounded-full" onClick={() => setCreating(true)} aria-label="ثبت بودجه">
                        <PlusIcon className="size-6" />
                    </Button>
                }
            />
            <PageBody>
                {!budgets ? (
                    <ListSkeleton rows={3} />
                ) : budgets.length === 0 ? (
                    <EmptyState icon={PiggyBankIcon} title="بودجه‌ای ثبت نشده" description="برای کنترل هزینه‌ها، برای هر دسته یک سقف تعیین کنید." action={<Button onClick={() => setCreating(true)}>ثبت بودجه</Button>} />
                ) : (
                    <div className="flex flex-col gap-3">
                        {budgets.map((budget) => {
                            const { progress } = budget;

                            return (
                                <div key={budget.id} className="flex flex-col gap-3 rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="truncate font-semibold">{budget.title}</p>
                                            <p className="text-xs text-muted-foreground">{budget.category?.name ?? 'همه هزینه‌ها'}</p>
                                        </div>
                                        <p className={cn('text-lg font-extrabold tabular-nums', progress.over_threshold ? 'text-expense' : 'text-income')}>{format.number(progress.percent)}٪</p>
                                    </div>
                                    <Progress
                                        value={Math.min(100, progress.percent)}
                                        aria-label="مصرف بودجه"
                                        className={cn('h-2.5', progress.over_threshold ? '[&_[data-slot=progress-indicator]]:bg-expense' : '[&_[data-slot=progress-indicator]]:bg-income')}
                                    />
                                    <div className="grid grid-cols-3 gap-2 text-xs text-muted-foreground">
                                        <div>
                                            <p>بودجه</p>
                                            <Money amount={budget.amount} className="text-sm font-semibold text-foreground" unitClassName="hidden" />
                                        </div>
                                        <div>
                                            <p>مصرف</p>
                                            <Money amount={progress.spent} className="text-sm font-semibold text-foreground" unitClassName="hidden" />
                                        </div>
                                        <div>
                                            <p>مانده</p>
                                            <Money amount={progress.remaining} className="text-sm font-semibold text-foreground" unitClassName="hidden" />
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </PageBody>
            <NewBudgetSheet open={creating} onOpenChange={setCreating} categories={categories ?? []} />
        </>
    );
}
