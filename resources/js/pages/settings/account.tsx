import { Head, useForm, usePage } from '@inertiajs/react';
import { DownloadIcon, Trash2Icon } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { FormField, SubmitButton } from '@/components/form-field';
import { PageBody, PageHeader, SectionTitle } from '@/components/page-header';
import { PasswordInput } from '@/components/password-input';
import { ResponsiveModal } from '@/components/responsive-modal';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';

function ProfileForm() {
    const { auth } = usePage().props;
    const form = useForm({ name: auth.user?.name ?? '', email: auth.user?.email ?? '' });
    const emailChanged = form.data.email.trim().toLowerCase() !== (auth.user?.email ?? '');

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(route('user-profile-information.update'), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} noValidate className="rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
            <FieldGroup className="gap-5">
                <FormField label={t('نام')} htmlFor="name" error={form.errors.name}>
                    <Input id="name" autoComplete="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} aria-invalid={form.errors.name ? true : undefined} />
                </FormField>
                <FormField
                    label={t('ایمیل')}
                    htmlFor="email"
                    error={form.errors.email}
                    description={emailChanged ? t('بعد از تغییر ایمیل، لینک تأیید برای نشانی تازه ارسال می‌شود و تا تأیید آن نمی‌توانید از برنامه استفاده کنید.') : undefined}
                >
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
                <SubmitButton processing={form.processing}>{t('ذخیره')}</SubmitButton>
            </FieldGroup>
        </form>
    );
}

function PasswordForm() {
    const form = useForm({ current_password: '', password: '', password_confirmation: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(route('user-password.update'), { preserveScroll: true, onSuccess: () => form.reset(), onError: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <form onSubmit={submit} noValidate className="rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
            <FieldGroup className="gap-5">
                <FormField label={t('رمز عبور فعلی')} htmlFor="current_password" error={form.errors.current_password}>
                    <PasswordInput id="current_password" autoComplete="current-password" value={form.data.current_password} onChange={(event) => form.setData('current_password', event.target.value)} aria-invalid={form.errors.current_password ? true : undefined} />
                </FormField>
                <FormField label={t('رمز عبور تازه')} htmlFor="password" error={form.errors.password} description={t('حداقل ۱۰ کاراکتر، شامل حرف و عدد')}>
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
                <SubmitButton processing={form.processing}>{t('تغییر رمز عبور')}</SubmitButton>
                <p className="text-xs text-muted-foreground">{t('با تغییر رمز عبور از بقیه‌ی دستگاه‌ها خارج می‌شوید.')}</p>
            </FieldGroup>
        </form>
    );
}

function DeleteAccount() {
    const [open, setOpen] = useState(false);
    const form = useForm({ password: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.delete(route('account.destroy'), { onError: () => form.reset() });
    };

    return (
        <>
            <Button variant="outline" className="w-full text-destructive" onClick={() => setOpen(true)}>
                <Trash2Icon />
                {t('حذف حساب و همه‌ی داده‌ها')}
            </Button>
            <ResponsiveModal
                open={open}
                onOpenChange={(next) => {
                    setOpen(next);
                    form.reset();
                    form.clearErrors();
                }}
                title={t('حذف دائمی حساب')}
                description={t('همه‌ی حساب‌ها، تراکنش‌ها و پشتیبان‌های شما برای همیشه پاک می‌شود و برگشت‌پذیر نیست. اگر می‌خواهید، اول از داده‌هایتان خروجی بگیرید.')}
            >
                <form onSubmit={submit} noValidate className="pb-4">
                    <FieldGroup className="gap-5">
                        <FormField label={t('رمز عبور')} htmlFor="delete_password" error={form.errors.password}>
                            <PasswordInput id="delete_password" autoComplete="current-password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} aria-invalid={form.errors.password ? true : undefined} />
                        </FormField>
                        <SubmitButton variant="destructive" size="lg" processing={form.processing}>
                            {t('حذف دائمی')}
                        </SubmitButton>
                    </FieldGroup>
                </form>
            </ResponsiveModal>
        </>
    );
}

export default function AccountSettings() {
    return (
        <>
            <Head title={t('مشخصات و رمز عبور')} />
            <PageHeader title={t('مشخصات و رمز عبور')} back={route('settings.index')} backComponent="settings/index" />
            <PageBody>
                <section>
                    <SectionTitle>{t('مشخصات')}</SectionTitle>
                    <ProfileForm />
                </section>

                <section>
                    <SectionTitle>{t('رمز عبور')}</SectionTitle>
                    <PasswordForm />
                </section>

                <section>
                    <SectionTitle>{t('داده‌های شما')}</SectionTitle>
                    <div className="flex flex-col gap-3 rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
                        <p className="text-sm leading-6 text-muted-foreground">{t('داده‌ها مال شماست: خروجی کامل همیشه رایگان است و هر زمان می‌توانید حساب را برای همیشه حذف کنید.')}</p>
                        <Button variant="outline" className="w-full" asChild>
                            <a href={route('account.export')}>
                                <DownloadIcon />
                                {t('دریافت همه‌ی داده‌ها (JSON)')}
                            </a>
                        </Button>
                        <DeleteAccount />
                    </div>
                </section>
            </PageBody>
        </>
    );
}
