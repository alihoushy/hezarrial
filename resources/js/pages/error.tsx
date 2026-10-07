import { Head, Link } from '@inertiajs/react';
import { LockIcon, SearchXIcon, ServerCrashIcon, TimerIcon, WrenchIcon, type LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';

const MESSAGES: Record<number, { title: string; description: string; icon: LucideIcon }> = {
    403: { title: 'دسترسی ندارید', description: 'شما اجازه دیدن این صفحه را ندارید.', icon: LockIcon },
    404: { title: 'صفحه پیدا نشد', description: 'آدرس درخواستی وجود ندارد یا حذف شده است.', icon: SearchXIcon },
    429: { title: 'تعداد درخواست‌ها زیاد است', description: 'چند لحظه صبر کنید و دوباره تلاش کنید.', icon: TimerIcon },
    500: { title: 'خطای سرور', description: 'مشکلی پیش آمد. اگر تکرار شد، گزارش خطا را بررسی کنید.', icon: ServerCrashIcon },
    503: { title: 'در حال به‌روزرسانی', description: 'برنامه موقتاً در دسترس نیست. کمی بعد دوباره سر بزنید.', icon: WrenchIcon },
};

export default function ErrorPage({ status }: { status: number }) {
    const { title, description, icon: Icon } = MESSAGES[status] ?? MESSAGES[500];

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
                    <Link href="/">بازگشت به خانه</Link>
                </Button>
            </main>
        </>
    );
}
