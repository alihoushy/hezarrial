import { Head, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { AuthCard } from '@/components/auth-card';
import { FormField, SubmitButton } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { toLatinDigits } from '@/lib/format';
import { t } from '@/lib/i18n';

export default function TwoFactorChallenge() {
    const [useRecovery, setUseRecovery] = useState(false);
    const form = useForm({ code: '', recovery_code: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => (useRecovery ? { recovery_code: data.recovery_code.trim() } : { code: toLatinDigits(data.code).replace(/\D/g, '') }));
        form.post(route('two-factor.login.store'), { onFinish: () => form.reset() });
    };

    return (
        <>
            <Head title={t('ورود دومرحله‌ای')} />
            <AuthCard
                title={t('ورود دومرحله‌ای')}
                description={useRecovery ? t('یکی از کدهای بازیابی را بنویسید.') : t('کد ۶ رقمی برنامه‌ی احراز هویت (Authenticator) را بنویسید.')}
            >
                <form onSubmit={submit} noValidate>
                    <FieldGroup className="gap-5">
                        {useRecovery ? (
                            <FormField label={t('کد بازیابی')} htmlFor="recovery_code" error={form.errors.recovery_code}>
                                <Input id="recovery_code" dir="ltr" className="text-start" autoFocus autoComplete="one-time-code" autoCapitalize="none" value={form.data.recovery_code} onChange={(event) => form.setData('recovery_code', event.target.value)} aria-invalid={form.errors.recovery_code ? true : undefined} />
                            </FormField>
                        ) : (
                            <FormField label={t('کد تأیید')} htmlFor="code" error={form.errors.code}>
                                <Input
                                    id="code"
                                    inputMode="numeric"
                                    dir="ltr"
                                    maxLength={6}
                                    className="text-center text-lg tracking-[0.5em]"
                                    autoFocus
                                    autoComplete="one-time-code"
                                    value={form.data.code}
                                    onChange={(event) => form.setData('code', event.target.value)}
                                    aria-invalid={form.errors.code ? true : undefined}
                                />
                            </FormField>
                        )}
                        <SubmitButton size="lg" processing={form.processing}>
                            {t('ورود')}
                        </SubmitButton>
                        <Button type="button" variant="ghost" onClick={() => { setUseRecovery((value) => !value); form.clearErrors(); }}>
                            {useRecovery ? t('استفاده از کد برنامه') : t('استفاده از کد بازیابی')}
                        </Button>
                    </FieldGroup>
                </form>
            </AuthCard>
        </>
    );
}
