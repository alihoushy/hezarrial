import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FormField, SubmitButton } from '@/components/form-field';
import { PageBody, PageHeader } from '@/components/page-header';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { toLatinDigits } from '@/lib/format';
import type { Person } from '@/types';

export default function PersonForm({ person }: { person?: Person | null }) {
    const editing = Boolean(person);
    const form = useForm({
        full_name: person?.full_name ?? '',
        mobile: person?.mobile ?? '',
        description: person?.description ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, mobile: toLatinDigits(data.mobile.trim()) }));

        if (person) {
            form.put(route('people.update', person.id));
        } else {
            form.post(route('people.store'));
        }
    };

    return (
        <>
            <Head title={editing ? 'ویرایش شخص' : 'شخص جدید'} />
            <PageHeader
                title={editing ? 'ویرایش شخص' : 'شخص جدید'}
                back={person ? route('people.show', person.id) : route('people.index')}
                backComponent={person ? 'people/show' : 'people/index'}
            />
            <PageBody className="pt-5">
                <form onSubmit={submit} noValidate>
                    <FieldGroup className="gap-6">
                        <FormField label="نام کامل" htmlFor="full_name" error={form.errors.full_name}>
                            <Input
                                id="full_name"
                                autoFocus={!editing}
                                autoComplete="off"
                                value={form.data.full_name}
                                onChange={(event) => form.setData('full_name', event.target.value)}
                                aria-invalid={form.errors.full_name ? true : undefined}
                            />
                        </FormField>
                        <FormField label="موبایل" htmlFor="mobile" optional error={form.errors.mobile}>
                            <Input
                                id="mobile"
                                type="tel"
                                inputMode="tel"
                                dir="ltr"
                                className="text-start"
                                autoComplete="off"
                                value={form.data.mobile}
                                onChange={(event) => form.setData('mobile', event.target.value)}
                                aria-invalid={form.errors.mobile ? true : undefined}
                            />
                        </FormField>
                        <FormField label="توضیح" htmlFor="description" optional error={form.errors.description}>
                            <Textarea id="description" rows={3} value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} />
                        </FormField>
                        <SubmitButton size="lg" processing={form.processing} className="mt-2">
                            ذخیره
                        </SubmitButton>
                    </FieldGroup>
                </form>
            </PageBody>
        </>
    );
}
