import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FormField, SubmitButton } from '@/components/form-field';
import { PasswordInput } from '@/components/password-input';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { toLatinDigits } from '@/lib/format';
import { t } from '@/lib/i18n';

export default function Setup() {
    const form = useForm({ name: '', email: '', mobile: '', password: '', password_confirmation: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, email: data.email.trim(), mobile: toLatinDigits(data.mobile.trim()) }));
        form.post(route('setup'), { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <>
            <Head title={t('راه‌اندازی')} />
            <div className="flex flex-col gap-6 rounded-3xl bg-card p-6 ring-1 ring-foreground/5">
                <div className="flex flex-col gap-1">
                    <h1 className="text-xl font-extrabold">{t('راه‌اندازی هزار ریال')}</h1>
                    <p className="text-sm text-muted-foreground">{t('ثبت‌نام فقط برای اولین کاربر فعال است.')}</p>
                </div>
                <form onSubmit={submit} noValidate>
                    <FieldGroup className="gap-5">
                        <FormField label={t('نام')} htmlFor="name" error={form.errors.name}>
                            <Input id="name" autoFocus autoComplete="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} aria-invalid={form.errors.name ? true : undefined} />
                        </FormField>
                        <FormField label={t('ایمیل')} htmlFor="email" optional error={form.errors.email}>
                            <Input
                                id="email"
                                type="email"
                                dir="ltr"
                                className="text-start"
                                autoComplete="email"
                                autoCapitalize="none"
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.target.value)}
                                aria-invalid={form.errors.email ? true : undefined}
                            />
                        </FormField>
                        <FormField label={t('موبایل')} htmlFor="mobile" optional error={form.errors.mobile} description={t('با ایمیل یا موبایل می‌توانید وارد شوید.')}>
                            <Input
                                id="mobile"
                                type="tel"
                                inputMode="tel"
                                dir="ltr"
                                className="text-start"
                                autoComplete="tel"
                                value={form.data.mobile}
                                onChange={(event) => form.setData('mobile', event.target.value)}
                                aria-invalid={form.errors.mobile ? true : undefined}
                            />
                        </FormField>
                        <FormField label={t('رمز عبور')} htmlFor="password" error={form.errors.password} description={t('حداقل ۱۰ کاراکتر')}>
                            <PasswordInput
                                id="password"
                                autoComplete="new-password"
                                value={form.data.password}
                                onChange={(event) => form.setData('password', event.target.value)}
                                aria-invalid={form.errors.password ? true : undefined}
                            />
                        </FormField>
                        <FormField label={t('تکرار رمز عبور')} htmlFor="password_confirmation">
                            <PasswordInput
                                id="password_confirmation"
                                autoComplete="new-password"
                                value={form.data.password_confirmation}
                                onChange={(event) => form.setData('password_confirmation', event.target.value)}
                            />
                        </FormField>
                        <SubmitButton size="lg" processing={form.processing}>
                            {t('ساخت حساب خصوصی')}
                        </SubmitButton>
                    </FieldGroup>
                </form>
            </div>
        </>
    );
}
