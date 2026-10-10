@php($registrationOpen = \App\Support\Registration::isOpen())
<x-site.layout
    title="قیمت‌ها"
    description="هزار ریال رایگان است. ثبت تراکنش، حساب‌ها، چک، وام، بدهی و گزارش‌های پایه رایگان و خروجی کامل داده‌ها همیشه رایگان می‌ماند؛ پلن ویژه بعداً اضافه می‌شود."
    path="/pricing"
    :breadcrumbs="[['label' => 'خانه', 'url' => '/'], ['label' => 'قیمت‌ها', 'url' => '/pricing']]">
    <section class="mx-auto max-w-5xl px-4 pt-10">
        <h1 class="text-center text-4xl leading-[1.4] font-extrabold">قیمت‌ها</h1>
        <p class="mx-auto mt-4 max-w-2xl text-center text-lg leading-9 text-muted-foreground">همه‌ی امکانات امروز رایگان است. بعداً یک پلن ویژه برای امکانات پیشرفته اضافه می‌شود؛ بخش رایگان همچنان رایگان می‌ماند.</p>

        <div class="mt-12 grid gap-6 md:grid-cols-2">
            <section class="flex flex-col rounded-3xl border-2 border-brand bg-card p-7">
                <p class="text-sm font-semibold text-brand">همین حالا</p>
                <h2 class="mt-1 text-2xl font-extrabold">رایگان</h2>
                <p class="mt-3 text-4xl font-extrabold">۰ <span class="text-base font-medium text-muted-foreground">تومان، برای همیشه</span></p>
                <ul class="mt-6 flex-1 space-y-3 text-sm leading-7">
                    @foreach (['حساب‌ها، کارت‌ها و تراکنش‌های نامحدود', 'بدهی و طلب، چک و اقساط وام', 'بودجه‌بندی و گزارش‌های مالی', 'تقویم شمسی، حالت تاریک، فارسی و انگلیسی', 'خروجی Excel/CSV و پشتیبان‌گیری', 'خروجی کامل داده‌ها (JSON) و حذف حساب، هر زمان', 'ورود دومرحله‌ای'] as $item)
                        <li class="flex gap-3"><span class="mt-1 text-income">✓</span>{{ $item }}</li>
                    @endforeach
                </ul>
                <a href="{{ $registrationOpen ? '/register' : '/login' }}" class="mt-8 inline-flex h-12 items-center justify-center rounded-xl bg-primary font-bold text-primary-foreground">{{ $registrationOpen ? 'شروع رایگان' : 'ورود به برنامه' }}</a>
            </section>

            <section class="flex flex-col rounded-3xl border border-dashed border-border bg-card/50 p-7">
                <p class="text-sm font-semibold text-warning">به‌زودی</p>
                <h2 class="mt-1 text-2xl font-extrabold">ویژه</h2>
                <p class="mt-3 text-lg font-bold text-muted-foreground">قیمت هنوز اعلام نشده است</p>
                <p class="mt-3 text-sm leading-7 text-muted-foreground">امکاناتی که اجرای آن‌ها هزینه دارد یا کار بیشتری می‌طلبد، در پلن ویژه می‌آید:</p>
                <ul class="mt-4 flex-1 space-y-3 text-sm leading-7">
                    @foreach ($planned as [$name])
                        <li class="flex gap-3"><span class="mt-1 text-muted-foreground">○</span>{{ $name }}</li>
                    @endforeach
                </ul>
                <a href="/contact" class="mt-8 inline-flex h-12 items-center justify-center rounded-xl border border-border font-semibold">نظر یا پیشنهاد دارید؟</a>
            </section>
        </div>

        <div class="mt-12 rounded-2xl border border-border bg-card p-6 text-sm leading-8">
            <p class="font-bold">وعده‌هایی که تغییر نمی‌کند</p>
            <ul class="mt-2 list-disc ps-6 text-muted-foreground">
                <li>داده‌های شما هرگز گروگان پرداخت نمی‌شود: خروجی کامل داده‌ها همیشه رایگان است.</li>
                <li>اگر اشتراک ویژه تمام شود، فقط امکانات ویژه خاموش می‌شود؛ به داده‌هایتان دسترسی دارید.</li>
                <li>کد برنامه متن‌باز است و می‌توانید نسخه‌ی خودتان را اجرا کنید.</li>
            </ul>
        </div>
    </section>
    <x-site.cta />
</x-site.layout>
