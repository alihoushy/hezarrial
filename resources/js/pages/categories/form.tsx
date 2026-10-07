import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { ConfirmAction } from '@/components/confirm-action';
import { FormField, SelectField, SubmitButton } from '@/components/form-field';
import { PageBody, PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { Category, CategoryOption, CategoryType } from '@/types';

const SWATCHES = ['#14b8a6', '#10b981', '#3b82f6', '#6366f1', '#a855f7', '#ec4899', '#ef4444', '#f97316', '#eab308', '#64748b'];

interface Props {
    category?: Category | null;
    parents?: CategoryOption[];
}

export default function CategoryForm({ category, parents = [] }: Props) {
    const editing = Boolean(category);
    const form = useForm({
        name: category?.name ?? '',
        type: (category?.type ?? 'expense') as CategoryType,
        parent_id: category?.parent_id ? String(category.parent_id) : '',
        color: category?.color ?? SWATCHES[0],
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (category) {
            form.put(route('categories.update', category.id));
        } else {
            form.post(route('categories.store'));
        }
    };

    return (
        <>
            <Head title={editing ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید'} />
            <PageHeader title={editing ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید'} back={route('categories.index')} backComponent="categories/index" />
            <PageBody className="pt-5">
                <form onSubmit={submit} noValidate>
                    <FieldGroup className="gap-6">
                        <FormField label="نام" htmlFor="name" error={form.errors.name}>
                            <Input
                                id="name"
                                autoFocus={!editing}
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                placeholder="مثلاً: خوراک"
                                aria-invalid={form.errors.name ? true : undefined}
                            />
                        </FormField>

                        <FormField label="نوع" htmlFor="type" error={form.errors.type}>
                            <SelectField
                                id="type"
                                value={form.data.type}
                                onChange={(event) => form.setData('type', event.target.value as CategoryType)}
                                options={[
                                    { value: 'expense', label: 'هزینه' },
                                    { value: 'income', label: 'درآمد' },
                                ]}
                            />
                        </FormField>

                        <FormField label="دسته والد" htmlFor="parent_id" optional error={form.errors.parent_id}>
                            <SelectField
                                id="parent_id"
                                value={form.data.parent_id}
                                onChange={(event) => form.setData('parent_id', event.target.value)}
                                placeholder="ندارد"
                                options={parents.map((parent) => ({ value: parent.id, label: parent.name }))}
                            />
                        </FormField>

                        <FormField label="رنگ" error={form.errors.color}>
                            <div className="flex flex-wrap gap-3" role="radiogroup" aria-label="رنگ">
                                {SWATCHES.map((color) => (
                                    <button
                                        key={color}
                                        type="button"
                                        role="radio"
                                        aria-checked={form.data.color === color}
                                        aria-label={color}
                                        onClick={() => form.setData('color', color)}
                                        className={cn('size-10 rounded-full ring-offset-2 ring-offset-background transition-transform active:scale-90', form.data.color === color && 'ring-2 ring-foreground')}
                                        style={{ background: color }}
                                    />
                                ))}
                            </div>
                        </FormField>

                        <div className="flex flex-col gap-2.5 pt-2">
                            <SubmitButton size="lg" processing={form.processing}>
                                ذخیره
                            </SubmitButton>
                            {category && (
                                <ConfirmAction
                                    title="بایگانی دسته‌بندی؟"
                                    description="دسته‌بندی از فهرست حذف می‌شود ولی تراکنش‌های قبلی آن باقی می‌ماند."
                                    confirmLabel="بایگانی"
                                    href={route('categories.destroy', category.id)}
                                    method="delete"
                                    destructive
                                >
                                    <Button type="button" size="lg" variant="destructive">
                                        بایگانی دسته‌بندی
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
