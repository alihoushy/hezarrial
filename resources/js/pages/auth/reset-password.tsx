import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthCard } from '@/components/auth-card';
import { FormField, SubmitButton } from '@/components/form-field';
import { PasswordInput } from '@/components/password-input';
import { FieldGroup } from '@/components/ui/field';
import { t } from '@/lib/i18n';

export default function ResetPassword({ token, email }: { token: string; email: string }) {
    const form = useForm({ token, email, password: '', password_confirmation: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('password.update'), { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <>
            <Head title={t('رمز عبور تازه')} />
            <AuthCard title={t('رمز عبور تازه')} description={email}>
                <form onSubmit={submit} noValidate>
                    <FieldGroup className="gap-5">
                        {(form.errors.email || form.errors.token) && <p className="text-sm text-destructive">{form.errors.email ?? form.errors.token}</p>}
                        <FormField label={t('رمز عبور')} htmlFor="password" error={form.errors.password} description={t('حداقل ۱۰ کاراکتر، شامل حرف و عدد')}>
                            <PasswordInput id="password" autoFocus autoComplete="new-password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} aria-invalid={form.errors.password ? true : undefined} />
                        </FormField>
                        <FormField label={t('تکرار رمز عبور')} htmlFor="password_confirmation" error={form.errors.password_confirmation}>
                            <PasswordInput
                                id="password_confirmation"
                                autoComplete="new-password"
                                value={form.data.password_confirmation}
                                onChange={(event) => form.setData('password_confirmation', event.target.value)}
                                aria-invalid={form.errors.password_confirmation ? true : undefined}
                            />
                        </FormField>
                        <SubmitButton size="lg" processing={form.processing}>
                            {t('ذخیره رمز عبور')}
                        </SubmitButton>
                    </FieldGroup>
                </form>
            </AuthCard>
        </>
    );
}
