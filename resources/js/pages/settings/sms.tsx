import { Head, useForm, usePage } from '@inertiajs/react';
import { CheckIcon, CopyIcon, KeyRoundIcon, SmartphoneIcon, Trash2Icon } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import { ConfirmAction } from '@/components/confirm-action';
import { FormField, SubmitButton } from '@/components/form-field';
import { ListCard, ListRow } from '@/components/list';
import { PageBody, PageHeader, SectionTitle } from '@/components/page-header';
import { ListSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { useFormat } from '@/lib/format';
import { t } from '@/lib/i18n';

interface Token {
    id: number;
    name: string;
    created_at: string | null;
    last_used_at: string | null;
}

interface Props {
    tokens?: Token[];
    endpoint?: string;
    limit?: number;
}

function CopyField({ value, label }: { value: string; label: string }) {
    const [copied, setCopied] = useState(false);

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(value);
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        } catch {
            // The value stays on screen, so it can still be copied by hand.
        }
    };

    return (
        <div className="flex flex-col gap-2">
            <code className="rounded-xl bg-muted px-3 py-2.5 text-xs leading-5 break-all" dir="ltr">
                {value}
            </code>
            <Button type="button" variant="outline" size="sm" className="self-start" onClick={copy}>
                {copied ? <CheckIcon /> : <CopyIcon />}
                {label}
            </Button>
        </div>
    );
}

export default function SmsSettings({ tokens, endpoint, limit }: Props) {
    const format = useFormat();
    const { flash } = usePage();
    const form = useForm({ name: '' });
    // The plain token arrives once, in flash data; keep it on screen until the user leaves.
    const [created, setCreated] = useState<string | null>(null);

    useEffect(() => {
        if (flash?.sms_token) {
            setCreated(flash.sms_token);
        }
    }, [flash?.sms_token]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('sms-tokens.store'), { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <>
            <Head title={t('اتصال پیامک بانکی')} />
            <PageHeader title={t('اتصال پیامک بانکی')} back={route('settings.index')} backComponent="settings/index" />
            <PageBody>
                <p className="px-1 text-sm leading-6 text-muted-foreground">
                    {t('با یک توکن شخصی، گوشی‌تان (میان‌بر آیفون یا یک برنامه‌ی فوروارد پیامک در اندروید) پیامک‌های بانک را به حساب شما می‌فرستد. هر توکن فقط اجازه‌ی ارسال پیامک دارد و هر زمان می‌توانید آن را حذف کنید.')}
                </p>

                {created && (
                    <section className="flex flex-col gap-3 rounded-2xl bg-primary/5 p-4 ring-1 ring-primary/30">
                        <p className="text-sm font-semibold">{t('توکن تازه‌ی شما')}</p>
                        <p className="text-xs leading-5 text-muted-foreground">{t('این توکن فقط همین یک بار نمایش داده می‌شود. آن را همین حالا در گوشی‌تان وارد کنید.')}</p>
                        <CopyField value={created} label={t('کپی توکن')} />
                    </section>
                )}

                <section>
                    <SectionTitle>{t('توکن جدید')}</SectionTitle>
                    <form onSubmit={submit} noValidate className="rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
                        <FieldGroup className="gap-4">
                            <FormField label={t('نام')} htmlFor="token_name" error={form.errors.name} description={t('مثلاً: آیفون من')}>
                                <Input id="token_name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} aria-invalid={form.errors.name ? true : undefined} />
                            </FormField>
                            <SubmitButton processing={form.processing}>{t('ساخت توکن')}</SubmitButton>
                        </FieldGroup>
                    </form>
                </section>

                <section>
                    <SectionTitle>{t('توکن‌های شما')}</SectionTitle>
                    {!tokens ? (
                        <ListSkeleton rows={2} />
                    ) : tokens.length === 0 ? (
                        <p className="rounded-2xl bg-card p-4 text-sm text-muted-foreground ring-1 ring-foreground/5">{t('هنوز توکنی نساخته‌اید.')}</p>
                    ) : (
                        <ListCard>
                            {tokens.map((token) => (
                                <ListRow
                                    key={token.id}
                                    icon={KeyRoundIcon}
                                    title={token.name}
                                    subtitle={token.last_used_at ? t('آخرین استفاده: :date', { date: format.dateTime(token.last_used_at) }) : t('هنوز استفاده نشده')}
                                    trailing={
                                        <ConfirmAction
                                            title={t('حذف توکن؟')}
                                            description={t('گوشی‌ای که از این توکن استفاده می‌کند دیگر نمی‌تواند پیامک بفرستد.')}
                                            confirmLabel={t('حذف')}
                                            href={route('sms-tokens.destroy', token.id)}
                                            method="delete"
                                            destructive
                                        >
                                            <Button type="button" variant="ghost" size="icon" aria-label={t('حذف توکن')}>
                                                <Trash2Icon className="size-4 text-destructive" />
                                            </Button>
                                        </ConfirmAction>
                                    }
                                />
                            ))}
                        </ListCard>
                    )}
                    {limit && <p className="mt-2 px-1 text-xs text-muted-foreground">{t('حداکثر :count توکن', { count: format.digits(String(limit)) })}</p>}
                </section>

                {endpoint && (
                    <section>
                        <SectionTitle>{t('راهنمای اتصال')}</SectionTitle>
                        <div className="flex flex-col gap-3 rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
                            <div className="flex items-center gap-2 text-sm font-semibold">
                                <SmartphoneIcon className="size-4" />
                                {t('درخواست POST به این نشانی')}
                            </div>
                            <CopyField value={endpoint} label={t('کپی نشانی')} />
                            <ul className="list-disc ps-5 text-xs leading-6 text-muted-foreground">
                                <li>{t('هدر Authorization با مقدار «Bearer توکن» (یا هدر X-Ingest-Token)')}</li>
                                <li>{t('بدنه‌ی JSON: {"message": "متن پیامک بانک"}')}</li>
                                <li>{t('پیامک‌ها در برنامه ثبت می‌شوند و کدهای یک‌بارمصرف بانک‌ها هرگز ذخیره نمی‌شوند.')}</li>
                            </ul>
                        </div>
                    </section>
                )}
            </PageBody>
        </>
    );
}
