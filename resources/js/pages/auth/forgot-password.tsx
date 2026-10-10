import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthCard } from '@/components/auth-card';
import { FormField, SubmitButton } from '@/components/form-field';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';

export default function ForgotPassword() {
    const form = useForm({ email: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('password.email'));
    };

    return (
        <>
            <Head title={t('بازیابی رمز عبور')} />
            <AuthCard
                title={t('بازیابی رمز عبور')}
                description={t('ایمیل حسابتان را بنویسید تا لینک تعیین رمز تازه برایتان ارسال شود.')}
                footer={
                    <Link href={route('login')} className="font-semibold text-foreground">
                        {t('بازگشت به ورود')}
                    </Link>
                }
            >
                <form onSubmit={submit} noValidate>
                    <FieldGroup className="gap-5">
                        <FormField label={t('ایمیل')} htmlFor="email" error={form.errors.email}>
                            <Input
                                id="email"
                                type="email"
                                dir="ltr"
                                className="text-start"
                                autoFocus
                                autoComplete="email"
                                autoCapitalize="none"
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.target.value)}
                                aria-invalid={form.errors.email ? true : undefined}
                            />
                        </FormField>
                        <SubmitButton size="lg" processing={form.processing}>
                            {t('ارسال لینک بازیابی')}
                        </SubmitButton>
                    </FieldGroup>
                </form>
            </AuthCard>
        </>
    );
}
