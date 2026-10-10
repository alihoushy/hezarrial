import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthCard } from '@/components/auth-card';
import { FormField, SubmitButton } from '@/components/form-field';
import { PasswordInput } from '@/components/password-input';
import { FieldGroup } from '@/components/ui/field';
import { t } from '@/lib/i18n';

export default function ConfirmPassword() {
    const form = useForm({ password: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('password.confirm.store'), { onFinish: () => form.reset() });
    };

    return (
        <>
            <Head title={t('تأیید رمز عبور')} />
            <AuthCard title={t('تأیید رمز عبور')} description={t('این بخش حساس است؛ برای ادامه رمز عبورتان را دوباره بنویسید.')}>
                <form onSubmit={submit} noValidate>
                    <FieldGroup className="gap-5">
                        <FormField label={t('رمز عبور')} htmlFor="password" error={form.errors.password}>
                            <PasswordInput id="password" autoFocus autoComplete="current-password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} aria-invalid={form.errors.password ? true : undefined} />
                        </FormField>
                        <SubmitButton size="lg" processing={form.processing}>
                            {t('ادامه')}
                        </SubmitButton>
                    </FieldGroup>
                </form>
            </AuthCard>
        </>
    );
}
