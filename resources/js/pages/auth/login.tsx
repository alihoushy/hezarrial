import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthCard } from '@/components/auth-card';
import { FormField, SubmitButton } from '@/components/form-field';
import { PasswordInput } from '@/components/password-input';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { toLatinDigits } from '@/lib/format';
import { t } from '@/lib/i18n';

export default function Login() {
    const { registration } = usePage().props;
    const form = useForm({ login: '', password: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        // A mobile number typed on a Persian keyboard arrives with Persian digits.
        form.transform((data) => ({ ...data, login: toLatinDigits(data.login.trim()) }));
        form.post(route('login.store'), { onFinish: () => form.reset('password') });
    };

    return (
        <>
            <Head title={t('ورود')} />
            <AuthCard
                title={t('ورود امن')}
                description={t('برای مدیریت مالی شخصی وارد شوید.')}
                footer={
                    registration && (
                        <>
                            {t('حساب ندارید؟')}{' '}
                            <Link href={route('register')} className="font-semibold text-foreground">
                                {t('ثبت‌نام')}
                            </Link>
                        </>
                    )
                }
            >
                <form onSubmit={submit} noValidate>
                    <FieldGroup className="gap-5">
                        <FormField label={t('ایمیل یا موبایل')} htmlFor="login" error={form.errors.login}>
                            <Input
                                id="login"
                                dir="ltr"
                                className="text-start"
                                autoFocus
                                autoComplete="username"
                                autoCapitalize="none"
                                autoCorrect="off"
                                spellCheck={false}
                                value={form.data.login}
                                onChange={(event) => form.setData('login', event.target.value)}
                                aria-invalid={form.errors.login ? true : undefined}
                            />
                        </FormField>
                        <FormField label={t('رمز عبور')} htmlFor="password" error={form.errors.password}>
                            <PasswordInput
                                id="password"
                                autoComplete="current-password"
                                value={form.data.password}
                                onChange={(event) => form.setData('password', event.target.value)}
                                aria-invalid={form.errors.password ? true : undefined}
                            />
                        </FormField>
                        <SubmitButton size="lg" processing={form.processing}>
                            {t('ورود')}
                        </SubmitButton>
                        <Link href={route('password.request')} className="text-center text-sm text-muted-foreground">
                            {t('رمز عبور را فراموش کرده‌ام')}
                        </Link>
                    </FieldGroup>
                </form>
            </AuthCard>
        </>
    );
}
