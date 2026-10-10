import { Head, router, useForm, usePage } from '@inertiajs/react';
import { MailCheckIcon } from 'lucide-react';
import type { FormEvent } from 'react';
import { AuthCard } from '@/components/auth-card';
import { SubmitButton } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { t } from '@/lib/i18n';

export default function VerifyEmail() {
    const { auth } = usePage().props;
    const resend = useForm({});

    const submit = (event: FormEvent) => {
        event.preventDefault();
        resend.post(route('verification.send'));
    };

    return (
        <>
            <Head title={t('تأیید ایمیل')} />
            <AuthCard title={t('ایمیل خود را تأیید کنید')} description={t('لینک تأیید به :email ارسال شد. روی آن بزنید تا حساب فعال شود. پوشه‌ی اسپم را هم ببینید.', { email: auth.user?.email ?? '' })}>
                <div className="flex size-14 items-center justify-center self-center rounded-2xl bg-primary/10 text-primary">
                    <MailCheckIcon className="size-7" />
                </div>
                <form onSubmit={submit} className="flex flex-col gap-3">
                    <SubmitButton size="lg" processing={resend.processing}>
                        {t('ارسال دوباره‌ی لینک')}
                    </SubmitButton>
                    <Button type="button" variant="ghost" onClick={() => router.post(route('logout'))}>
                        {t('خروج')}
                    </Button>
                </form>
            </AuthCard>
        </>
    );
}
