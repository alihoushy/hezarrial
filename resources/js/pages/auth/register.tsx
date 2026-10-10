import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthCard } from '@/components/auth-card';
import { FormField, SubmitButton } from '@/components/form-field';
import { PasswordInput } from '@/components/password-input';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';

export default function Register({ formToken }: { formToken?: string }) {
    const form = useForm({ name: '', email: '', password: '', password_confirmation: '', website: '', form_token: formToken ?? '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('register.store'), { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <>
            <Head title={t('ثبت‌نام')} />
            <AuthCard
                title={t('ساخت حساب')}
                description={t('حساب‌ها و تراکنش‌هایتان را در یک جا نگه دارید؛ رایگان شروع کنید.')}
                footer={
                    <>
                        {t('حساب دارید؟')}{' '}
                        <Link href={route('login')} className="font-semibold text-foreground">
                            {t('ورود')}
                        </Link>
                    </>
                }
            >
                <form onSubmit={submit} noValidate>
                    <FieldGroup className="gap-5">
                        <FormField label={t('نام')} htmlFor="name" error={form.errors.name}>
                            <Input id="name" autoFocus autoComplete="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} aria-invalid={form.errors.name ? true : undefined} />
                        </FormField>
                        <FormField label={t('ایمیل')} htmlFor="email" error={form.errors.email}>
                            <Input
                                id="email"
                                type="email"
                                dir="ltr"
                                className="text-start"
                                autoComplete="email"
                                autoCapitalize="none"
                                autoCorrect="off"
                                spellCheck={false}
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.target.value)}
                                aria-invalid={form.errors.email ? true : undefined}
                            />
                        </FormField>
                        <FormField label={t('رمز عبور')} htmlFor="password" error={form.errors.password} description={t('حداقل ۱۰ کاراکتر، شامل حرف و عدد')}>
                            <PasswordInput id="password" autoComplete="new-password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} aria-invalid={form.errors.password ? true : undefined} />
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

                        {/* Honeypot: invisible to people, tempting to bots. */}
                        <div aria-hidden="true" className="absolute -start-[9999px] h-0 w-0 overflow-hidden">
                            <label htmlFor="website">Website</label>
                            <input id="website" name="website" tabIndex={-1} autoComplete="off" value={form.data.website} onChange={(event) => form.setData('website', event.target.value)} />
                        </div>

                        <SubmitButton size="lg" processing={form.processing}>
                            {t('ساخت حساب')}
                        </SubmitButton>
                    </FieldGroup>
                </form>
            </AuthCard>
        </>
    );
}
