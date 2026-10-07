import { Head, Link } from '@inertiajs/react';
import { LockIcon, SearchXIcon, ServerCrashIcon, TimerIcon, WrenchIcon, type LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { t, tr } from '@/lib/i18n';

const MESSAGES: Record<number, { title: string; description: string; icon: LucideIcon }> = {
    403: { title: tr('دسترسی ندارید'), description: tr('شما اجازه دیدن این صفحه را ندارید.'), icon: LockIcon },
    404: { title: tr('صفحه پیدا نشد'), description: tr('آدرس درخواستی وجود ندارد یا حذف شده است.'), icon: SearchXIcon },
    429: { title: tr('تعداد درخواست‌ها زیاد است'), description: tr('چند لحظه صبر کنید و دوباره تلاش کنید.'), icon: TimerIcon },
    500: { title: tr('خطای سرور'), description: tr('مشکلی پیش آمد. اگر تکرار شد، گزارش خطا را بررسی کنید.'), icon: ServerCrashIcon },
    503: { title: tr('در حال به‌روزرسانی'), description: tr('برنامه موقتاً در دسترس نیست. کمی بعد دوباره سر بزنید.'), icon: WrenchIcon },
};

export default function ErrorPage({ status }: { status: number }) {
    const { title: titleKey, description: descriptionKey, icon: Icon } = MESSAGES[status] ?? MESSAGES[500];
    const title = t(titleKey);
    const description = t(descriptionKey);

    return (
        <>
            <Head title={title} />
            <main className="pt-safe pb-safe flex min-h-dvh flex-col items-center justify-center gap-5 px-6 text-center">
                <span className="flex size-16 items-center justify-center rounded-full bg-muted">
                    <Icon className="size-8 text-muted-foreground" />
                </span>
                <div className="flex flex-col gap-2">
                    <p className="text-sm font-medium text-muted-foreground tabular-nums" dir="ltr">
                        {status}
                    </p>
                    <h1 className="text-2xl font-extrabold">{title}</h1>
                    <p className="max-w-xs text-sm text-muted-foreground">{description}</p>
                </div>
                <Button size="lg" asChild>
                    <Link href="/">{t('بازگشت به خانه')}</Link>
                </Button>
            </main>
        </>
    );
}
