import { Head, router, useForm } from '@inertiajs/react';
import { BellIcon, CheckIcon, PlusIcon, XIcon } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { EmptyState } from '@/components/empty-state';
import { DateInput, FormField, SubmitButton } from '@/components/form-field';
import { ListCard, ListRow, StatusBadge } from '@/components/list';
import { PageBody, PageHeader, SectionTitle } from '@/components/page-header';
import { ResponsiveModal } from '@/components/responsive-modal';
import { ListSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { todayIso, useFormat } from '@/lib/format';
import { reminderStatuses } from '@/lib/labels';
import type { Reminder } from '@/types';
import { t } from '@/lib/i18n';

function NewReminderSheet({ open, onOpenChange }: { open: boolean; onOpenChange: (open: boolean) => void }) {
    const form = useForm({ title: '', due_date: todayIso(), due_time: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('reminders.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <ResponsiveModal open={open} onOpenChange={onOpenChange} title={t('یادآوری جدید')}>
            <form onSubmit={submit} noValidate className="pb-4">
                <FieldGroup className="gap-5">
                    <FormField label={t('عنوان')} htmlFor="reminder_title" error={form.errors.title}>
                        <Input id="reminder_title" autoFocus value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} placeholder={t('مثلاً: پرداخت قبض برق')} aria-invalid={form.errors.title ? true : undefined} />
                    </FormField>
                    <FormField label={t('تاریخ')} htmlFor="reminder_date" error={form.errors.due_date}>
                        <DateInput id="reminder_date" value={form.data.due_date} onChange={(event) => form.setData('due_date', event.target.value)} />
                    </FormField>
                    <FormField label={t('ساعت')} htmlFor="reminder_time" optional error={form.errors.due_time}>
                        <Input id="reminder_time" type="time" dir="ltr" className="text-start" value={form.data.due_time} onChange={(event) => form.setData('due_time', event.target.value)} />
                    </FormField>
                    <SubmitButton size="lg" processing={form.processing}>
                        {t('ثبت یادآوری')}
                    </SubmitButton>
                </FieldGroup>
            </form>
        </ResponsiveModal>
    );
}

function ReminderRow({ reminder }: { reminder: Reminder }) {
    const format = useFormat();
    const status = reminderStatuses[reminder.status];
    const pending = reminder.status === 'pending';
    const update = (next: 'done' | 'dismissed') => router.patch(route('reminders.update', reminder.id), { status: next }, { preserveScroll: true });

    return (
        <ListRow
            icon={BellIcon}
            iconClassName={pending ? 'bg-brand/12 text-brand' : undefined}
            title={reminder.title}
            subtitle={[format.relativeDay(reminder.due_date), reminder.due_time ? format.digits(reminder.due_time) : null].filter(Boolean).join(' · ')}
            trailing={
                pending ? (
                    <span className="flex items-center gap-1.5">
                        <Button variant="outline" size="icon-sm" className="rounded-full text-income" onClick={() => update('done')} aria-label={t('انجام شد')}>
                            <CheckIcon />
                        </Button>
                        <Button variant="outline" size="icon-sm" className="rounded-full text-muted-foreground" onClick={() => update('dismissed')} aria-label={t('نادیده گرفتن')}>
                            <XIcon />
                        </Button>
                    </span>
                ) : (
                    <StatusBadge label={status.label} tone={status.tone} />
                )
            }
        />
    );
}

export default function RemindersIndex({ reminders }: { reminders?: Reminder[] }) {
    const [creating, setCreating] = useState(false);
    const pending = reminders?.filter((reminder) => reminder.status === 'pending') ?? [];
    const finished = reminders?.filter((reminder) => reminder.status !== 'pending') ?? [];

    return (
        <>
            <Head title={t('یادآوری‌ها')} />
            <PageHeader
                title={t('یادآوری‌ها')}
                back={route('settings.index')}
                backComponent="settings/index"
                actions={
                    <Button size="icon" variant="ghost" className="rounded-full" onClick={() => setCreating(true)} aria-label={t('یادآوری جدید')}>
                        <PlusIcon className="size-6" />
                    </Button>
                }
            />
            <PageBody>
                {!reminders ? (
                    <ListSkeleton rows={4} />
                ) : reminders.length === 0 ? (
                    <EmptyState icon={BellIcon} title={t('یادآوری‌ای ندارید')} description={t('موعد قبض‌ها و پرداخت‌ها را اینجا ثبت کنید.')} action={<Button onClick={() => setCreating(true)}>{t('یادآوری جدید')}</Button>} />
                ) : (
                    <>
                        {pending.length > 0 && (
                            <section>
                                <SectionTitle>{t('در انتظار')}</SectionTitle>
                                <ListCard>
                                    {pending.map((reminder) => (
                                        <ReminderRow key={reminder.id} reminder={reminder} />
                                    ))}
                                </ListCard>
                            </section>
                        )}
                        {finished.length > 0 && (
                            <section>
                                <SectionTitle>{t('انجام‌شده و نادیده‌گرفته')}</SectionTitle>
                                <ListCard className="opacity-80">
                                    {finished.map((reminder) => (
                                        <ReminderRow key={reminder.id} reminder={reminder} />
                                    ))}
                                </ListCard>
                            </section>
                        )}
                    </>
                )}
            </PageBody>
            <NewReminderSheet open={creating} onOpenChange={setCreating} />
        </>
    );
}
