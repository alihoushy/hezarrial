import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FormField, SubmitButton } from '@/components/form-field';
import { PasswordInput } from '@/components/password-input';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { toLatinDigits } from '@/lib/format';

export default function Login() {
    const form = useForm({ login: '', password: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        // A mobile number typed on a Persian keyboard arrives with Persian digits.
        form.transform((data) => ({ ...data, login: toLatinDigits(data.login.trim()) }));
        form.post(route('login'), { onFinish: () => form.reset('password') });
    };

    return (
        <>
            <Head title="ورود" />
            <div className="flex flex-col gap-6 rounded-3xl bg-card p-6 ring-1 ring-foreground/5">
                <div className="flex flex-col gap-1">
                    <h1 className="text-xl font-extrabold">ورود امن</h1>
                    <p className="text-sm text-muted-foreground">برای مدیریت مالی شخصی وارد شوید.</p>
                </div>
                <form onSubmit={submit} noValidate>
                    <FieldGroup className="gap-5">
                        <FormField label="ایمیل یا موبایل" htmlFor="login" error={form.errors.login}>
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
                        <FormField label="رمز عبور" htmlFor="password" error={form.errors.password}>
                            <PasswordInput
                                id="password"
                                autoComplete="current-password"
                                value={form.data.password}
                                onChange={(event) => form.setData('password', event.target.value)}
                                aria-invalid={form.errors.password ? true : undefined}
                            />
                        </FormField>
                        <SubmitButton size="lg" processing={form.processing}>
                            ورود
                        </SubmitButton>
                    </FieldGroup>
                </form>
            </div>
        </>
    );
}
