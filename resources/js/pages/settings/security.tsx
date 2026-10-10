import { Head, router, useForm } from '@inertiajs/react';
import { CheckIcon, CopyIcon, LaptopIcon, ShieldCheckIcon } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { ConfirmAction } from '@/components/confirm-action';
import { FormField, SubmitButton } from '@/components/form-field';
import { ListCard, ListRow } from '@/components/list';
import { PageBody, PageHeader, SectionTitle } from '@/components/page-header';
import { PasswordInput } from '@/components/password-input';
import { ResponsiveModal } from '@/components/responsive-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useFormat } from '@/lib/format';
import { t } from '@/lib/i18n';

interface TwoFactor {
    enabled: boolean;
    confirmed: boolean;
    qr_svg: string | null;
    setup_key: string | null;
    recovery_codes: string[];
}

interface SessionRow {
    device: string;
    ip: string | null;
    last_active: string;
    current: boolean;
}

function CopyButton({ text, label }: { text: string; label: string }) {
    const [copied, setCopied] = useState(false);

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(text);
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        } catch {
            // Clipboard access can be refused; the text is still on screen to copy by hand.
        }
    };

    return (
        <Button type="button" variant="outline" size="sm" onClick={copy}>
            {copied ? <CheckIcon /> : <CopyIcon />}
            {label}
        </Button>
    );
}

function ConfirmSetup({ twoFactor }: { twoFactor: TwoFactor }) {
    const form = useForm({ code: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('two-factor.confirm'), { preserveScroll: true, onFinish: () => form.reset() });
    };

    return (
        <div className="flex flex-col gap-4">
            <p className="text-sm leading-6 text-muted-foreground">{t('یک برنامه‌ی احراز هویت (مثل Google Authenticator) باز کنید، این کد QR را اسکن کنید و کد ۶ رقمی را بنویسید.')}</p>
            {twoFactor.qr_svg && <div className="mx-auto w-44 rounded-2xl bg-white p-3 ring-1 ring-black/10 [&>svg]:h-auto [&>svg]:w-full" dangerouslySetInnerHTML={{ __html: twoFactor.qr_svg }} />}
            {twoFactor.setup_key && (
                <div className="flex flex-col items-center gap-2">
                    <p className="text-xs text-muted-foreground">{t('اگر نمی‌توانید اسکن کنید، این کلید را وارد کنید:')}</p>
                    <code className="rounded-lg bg-muted px-3 py-1.5 text-sm tracking-wider" dir="ltr">
                        {twoFactor.setup_key}
                    </code>
                    <CopyButton text={twoFactor.setup_key} label={t('کپی کلید')} />
                </div>
            )}
            <form onSubmit={submit} noValidate>
                <FieldGroup className="gap-4">
                    <FormField label={t('کد تأیید')} htmlFor="code" error={form.errors.code}>
                        <Input id="code" inputMode="numeric" dir="ltr" maxLength={6} className="text-center text-lg tracking-[0.5em]" autoComplete="one-time-code" value={form.data.code} onChange={(event) => form.setData('code', event.target.value)} aria-invalid={form.errors.code ? true : undefined} />
                    </FormField>
                    <SubmitButton processing={form.processing}>{t('تأیید و فعال‌سازی')}</SubmitButton>
                    <Button type="button" variant="ghost" onClick={() => router.delete(route('two-factor.disable'), { preserveScroll: true })}>
                        {t('انصراف')}
                    </Button>
                </FieldGroup>
            </form>
        </div>
    );
}

function TwoFactorSection({ twoFactor }: { twoFactor: TwoFactor }) {
    const [busy, setBusy] = useState(false);
    const enable = () => router.post(route('two-factor.enable'), {}, { preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) });

    return (
        <section>
            <SectionTitle>{t('ورود دومرحله‌ای')}</SectionTitle>
            <div className="flex flex-col gap-4 rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
                {!twoFactor.enabled && (
                    <>
                        <p className="text-sm leading-6 text-muted-foreground">{t('با ورود دومرحله‌ای، حتی اگر کسی رمز عبور شما را بداند بدون کد برنامه‌ی احراز هویت وارد نمی‌شود.')}</p>
                        <Button onClick={enable} disabled={busy}>
                            {busy ? <Spinner /> : <ShieldCheckIcon />}
                            {t('فعال‌سازی')}
                        </Button>
                    </>
                )}

                {twoFactor.enabled && !twoFactor.confirmed && <ConfirmSetup twoFactor={twoFactor} />}

                {twoFactor.confirmed && (
                    <>
                        <div className="flex items-center gap-2">
                            <Badge>{t('فعال')}</Badge>
                            <span className="text-sm text-muted-foreground">{t('ورود شما با کد برنامه‌ی احراز هویت محافظت می‌شود.')}</span>
                        </div>
                        <div className="flex flex-col gap-3">
                            <p className="text-sm font-semibold">{t('کدهای بازیابی')}</p>
                            <p className="text-xs leading-5 text-muted-foreground">{t('اگر گوشی‌تان را گم کردید، هر کد را یک بار می‌توانید به‌جای کد برنامه استفاده کنید. آن‌ها را جای امنی نگه دارید.')}</p>
                            <div className="grid grid-cols-2 gap-2 rounded-xl bg-muted p-3 text-center text-sm" dir="ltr">
                                {twoFactor.recovery_codes.map((code) => (
                                    <code key={code}>{code}</code>
                                ))}
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <CopyButton text={twoFactor.recovery_codes.join('\n')} label={t('کپی کدها')} />
                                <Button type="button" variant="outline" size="sm" onClick={() => router.post(route('two-factor.regenerate-recovery-codes'), {}, { preserveScroll: true })}>
                                    {t('ساخت دوباره‌ی کدها')}
                                </Button>
                            </div>
                        </div>
                        <ConfirmAction
                            title={t('غیرفعال کردن ورود دومرحله‌ای؟')}
                            description={t('ورود شما فقط با رمز عبور انجام می‌شود و امنیت حساب کمتر می‌شود.')}
                            confirmLabel={t('غیرفعال‌سازی')}
                            href={route('two-factor.disable')}
                            method="delete"
                            destructive
                        >
                            <Button variant="outline" className="text-destructive">
                                {t('غیرفعال‌سازی')}
                            </Button>
                        </ConfirmAction>
                    </>
                )}
            </div>
        </section>
    );
}

function SignOutOthers() {
    const [open, setOpen] = useState(false);
    const form = useForm({ password: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('security.sign-out-others'), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                form.reset();
            },
            onError: () => form.reset(),
        });
    };

    return (
        <>
            <Button variant="outline" className="w-full" onClick={() => setOpen(true)}>
                {t('خروج از بقیه‌ی دستگاه‌ها')}
            </Button>
            <ResponsiveModal open={open} onOpenChange={setOpen} title={t('خروج از بقیه‌ی دستگاه‌ها')} description={t('برای اطمینان، رمز عبورتان را بنویسید.')}>
                <form onSubmit={submit} noValidate className="pb-4">
                    <FieldGroup className="gap-5">
                        <FormField label={t('رمز عبور')} htmlFor="sessions_password" error={form.errors.password}>
                            <PasswordInput id="sessions_password" autoComplete="current-password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} aria-invalid={form.errors.password ? true : undefined} />
                        </FormField>
                        <SubmitButton size="lg" processing={form.processing}>
                            {t('خروج')}
                        </SubmitButton>
                    </FieldGroup>
                </form>
            </ResponsiveModal>
        </>
    );
}

export default function Security({ twoFactor, sessions }: { twoFactor?: TwoFactor; sessions?: SessionRow[] }) {
    const format = useFormat();

    return (
        <>
            <Head title={t('امنیت و دستگاه‌ها')} />
            <PageHeader title={t('امنیت و دستگاه‌ها')} back={route('settings.index')} backComponent="settings/index" />
            <PageBody>
                {twoFactor && <TwoFactorSection twoFactor={twoFactor} />}

                {sessions && (
                    <section>
                        <SectionTitle>{t('دستگاه‌های وارد‌شده')}</SectionTitle>
                        <ListCard>
                            {sessions.map((session, index) => (
                                <ListRow
                                    key={index}
                                    icon={LaptopIcon}
                                    title={
                                        <span className="flex items-center gap-2">
                                            {session.device}
                                            {session.current && <Badge variant="secondary">{t('این دستگاه')}</Badge>}
                                        </span>
                                    }
                                    subtitle={[session.ip, format.dateTime(session.last_active)].filter(Boolean).join(' · ')}
                                />
                            ))}
                        </ListCard>
                        <div className="mt-3">
                            <SignOutOthers />
                        </div>
                    </section>
                )}
            </PageBody>
        </>
    );
}
