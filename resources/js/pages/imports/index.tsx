import { Head, useForm } from '@inertiajs/react';
import { FileUpIcon, MessageSquareTextIcon, PlusIcon } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { FormField, SelectField, SubmitButton } from '@/components/form-field';
import { ListCard, ListRow, StatusBadge } from '@/components/list';
import { PageBody, PageHeader, SectionTitle } from '@/components/page-header';
import { ResponsiveModal } from '@/components/responsive-modal';
import { ListSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useFormat } from '@/lib/format';
import { importStatuses } from '@/lib/labels';
import type { ImportRecord, SmsPattern } from '@/types';
import { t } from '@/lib/i18n';

function PatternSheet({ open, onOpenChange }: { open: boolean; onOpenChange: (open: boolean) => void }) {
    const form = useForm({ name: '', bank_name: '', pattern: '', debit_keywords: '', credit_keywords: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('imports.sms-patterns.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <ResponsiveModal open={open} onOpenChange={onOpenChange} title={t('الگوی پیامک بانک')} description={t('برای بانک‌هایی که تشخیص خودکار آن‌ها را درست نمی‌خواند.')}>
            <form onSubmit={submit} noValidate className="pb-4">
                <FieldGroup className="gap-5">
                    <FormField label={t('نام الگو')} htmlFor="pattern_name" error={form.errors.name}>
                        <Input id="pattern_name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} aria-invalid={form.errors.name ? true : undefined} />
                    </FormField>
                    <FormField label={t('نام بانک')} htmlFor="pattern_bank" optional error={form.errors.bank_name}>
                        <Input id="pattern_bank" value={form.data.bank_name} onChange={(event) => form.setData('bank_name', event.target.value)} />
                    </FormField>
                    <FormField label={t('عبارت منظم (Regex)')} htmlFor="pattern_regex" error={form.errors.pattern} description={t('با گروه‌های amount، type، balance، date و card')}>
                        <Textarea
                            id="pattern_regex"
                            dir="ltr"
                            rows={4}
                            className="font-mono text-start text-sm"
                            value={form.data.pattern}
                            onChange={(event) => form.setData('pattern', event.target.value)}
                            aria-invalid={form.errors.pattern ? true : undefined}
                        />
                    </FormField>
                    <FormField label={t('کلیدواژه‌های برداشت')} htmlFor="pattern_debit" optional error={form.errors.debit_keywords} description={t('با ویرگول جدا کنید')}>
                        <Input id="pattern_debit" value={form.data.debit_keywords} onChange={(event) => form.setData('debit_keywords', event.target.value)} />
                    </FormField>
                    <FormField label={t('کلیدواژه‌های واریز')} htmlFor="pattern_credit" optional error={form.errors.credit_keywords} description={t('با ویرگول جدا کنید')}>
                        <Input id="pattern_credit" value={form.data.credit_keywords} onChange={(event) => form.setData('credit_keywords', event.target.value)} />
                    </FormField>
                    <SubmitButton size="lg" processing={form.processing}>
                        {t('ذخیره الگو')}
                    </SubmitButton>
                </FieldGroup>
            </form>
        </ResponsiveModal>
    );
}

export default function ImportsIndex({ imports, smsPatterns }: { imports?: ImportRecord[]; smsPatterns?: SmsPattern[] }) {
    const format = useFormat();
    const [patternSheet, setPatternSheet] = useState(false);
    const statementForm = useForm<{ statement: File | null }>({ statement: null });
    const smsForm = useForm({ sms_pattern_id: '', sms_text: '' });

    const uploadStatement = (event: FormEvent) => {
        event.preventDefault();
        statementForm.post(route('imports.csv'), { forceFormData: true, preserveScroll: true, onSuccess: () => statementForm.reset() });
    };

    const previewSms = (event: FormEvent) => {
        event.preventDefault();
        smsForm.post(route('imports.sms-preview'));
    };

    return (
        <>
            <Head title={t('وارد کردن داده')} />
            <PageHeader title={t('وارد کردن داده')} back={route('settings.index')} backComponent="settings/index" />
            <PageBody>
                <form onSubmit={previewSms} noValidate className="rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
                    <FieldGroup className="gap-4">
                        <div className="flex items-center gap-2 font-bold">
                            <MessageSquareTextIcon className="size-5 text-brand" />
                            {t('پیش‌نمایش پیامک بانکی')}
                        </div>
                        <FormField label={t('الگو')} htmlFor="sms_pattern" optional error={smsForm.errors.sms_pattern_id}>
                            <SelectField
                                id="sms_pattern"
                                value={smsForm.data.sms_pattern_id}
                                onChange={(event) => smsForm.setData('sms_pattern_id', event.target.value)}
                                placeholder={t('تشخیص خودکار')}
                                options={(smsPatterns ?? []).map((pattern) => ({ value: pattern.id, label: pattern.name }))}
                            />
                        </FormField>
                        <FormField label={t('متن پیامک')} htmlFor="sms_text" error={smsForm.errors.sms_text}>
                            <Textarea id="sms_text" rows={5} value={smsForm.data.sms_text} onChange={(event) => smsForm.setData('sms_text', event.target.value)} placeholder={t('متن پیامک را اینجا بچسبانید')} aria-invalid={smsForm.errors.sms_text ? true : undefined} />
                        </FormField>
                        <SubmitButton processing={smsForm.processing}>{t('تحلیل پیامک')}</SubmitButton>
                    </FieldGroup>
                </form>

                <form onSubmit={uploadStatement} noValidate className="rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
                    <FieldGroup className="gap-4">
                        <div className="flex items-center gap-2 font-bold">
                            <FileUpIcon className="size-5 text-brand" />
                            {t('صورت‌حساب CSV / Excel')}
                        </div>
                        <FormField label={t('فایل')} htmlFor="statement" error={statementForm.errors.statement} description={t('حداکثر ۱۰ مگابایت')}>
                            <Input id="statement" type="file" accept=".csv,.txt,.xlsx" onChange={(event) => statementForm.setData('statement', event.target.files?.[0] ?? null)} className="h-auto py-2" />
                        </FormField>
                        <SubmitButton processing={statementForm.processing} disabled={!statementForm.data.statement}>
                            {t('ارسال فایل')}
                        </SubmitButton>
                    </FieldGroup>
                </form>

                <section>
                    <SectionTitle
                        action={
                            <Button variant="ghost" size="sm" className="text-brand" onClick={() => setPatternSheet(true)}>
                                <PlusIcon />
                                {t('الگوی جدید')}
                            </Button>
                        }
                    >
                        {t('الگوهای پیامک')}
                    </SectionTitle>
                    {!smsPatterns ? (
                        <ListSkeleton rows={2} />
                    ) : smsPatterns.length === 0 ? (
                        <p className="rounded-2xl bg-card px-4 py-6 text-center text-sm text-muted-foreground ring-1 ring-foreground/5">{t('الگویی ذخیره نشده است.')}</p>
                    ) : (
                        <ListCard>
                            {smsPatterns.map((pattern) => (
                                <ListRow key={pattern.id} icon={MessageSquareTextIcon} title={pattern.name} subtitle={pattern.bank_name || t('بدون بانک')} />
                            ))}
                        </ListCard>
                    )}
                </section>

                {imports && imports.length > 0 && (
                    <section>
                        <SectionTitle>{t('سابقه ورود داده')}</SectionTitle>
                        <ListCard>
                            {imports.map((record) => {
                                const status = importStatuses[record.status];

                                return (
                                    <ListRow
                                        key={record.id}
                                        icon={record.type === 'csv' ? FileUpIcon : MessageSquareTextIcon}
                                        title={record.type === 'csv' ? t('صورت‌حساب') : t('پیامک')}
                                        subtitle={format.dateTime(record.created_at)}
                                        trailing={<StatusBadge label={status.label} tone={status.tone} />}
                                    />
                                );
                            })}
                        </ListCard>
                    </section>
                )}
            </PageBody>
            <PatternSheet open={patternSheet} onOpenChange={setPatternSheet} />
        </>
    );
}
