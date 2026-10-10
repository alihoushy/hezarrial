<x-site.layout
    title="تماس با ما"
    description="پرسش، پیشنهاد یا گزارش مشکل دارید؟ برای تیم هزار ریال بنویسید؛ معمولاً ظرف چند روز پاسخ می‌دهیم."
    path="/contact"
    :breadcrumbs="[['label' => 'خانه', 'url' => '/'], ['label' => 'تماس با ما', 'url' => '/contact']]">
    <section class="mx-auto max-w-2xl px-4 pt-10">
        <h1 class="text-4xl leading-[1.4] font-extrabold">تماس با ما</h1>
        <p class="mt-4 leading-9 text-muted-foreground">پرسش یا پیشنهادی دارید؟ بنویسید. اگر مشکل فنی دیده‌اید، می‌توانید در <a href="https://github.com/alihoushy/hezarrial/issues" rel="noopener" class="text-brand underline underline-offset-4">GitHub</a> هم Issue بسازید. مشکل امنیتی را به‌صورت خصوصی گزارش کنید (<a href="/security" class="text-brand underline underline-offset-4">راهنما</a>).</p>

        @if (session('sent'))
            <div class="mt-8 rounded-2xl border border-income/40 bg-income/10 p-5 leading-8" role="status">پیام شما ثبت شد. ممنون! اگر نیاز به پاسخ باشد، با ایمیلی که نوشتید تماس می‌گیریم.</div>
        @else
            <form method="post" action="/contact" class="mt-8 flex flex-col gap-5 rounded-2xl border border-border bg-card p-6">
                @csrf
                <label class="flex flex-col gap-2 text-sm font-semibold">نام
                    <input name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" class="h-12 rounded-xl border border-input bg-background px-3 text-base font-normal">
                    @error('name')<span class="text-xs font-normal text-destructive">{{ $message }}</span>@enderror
                </label>
                <label class="flex flex-col gap-2 text-sm font-semibold">ایمیل
                    <input name="email" type="email" dir="ltr" value="{{ old('email') }}" required maxlength="190" autocomplete="email" class="h-12 rounded-xl border border-input bg-background px-3 text-start text-base font-normal">
                    @error('email')<span class="text-xs font-normal text-destructive">{{ $message }}</span>@enderror
                </label>
                <label class="flex flex-col gap-2 text-sm font-semibold">پیام
                    <textarea name="message" rows="6" required maxlength="3000" class="rounded-xl border border-input bg-background p-3 text-base font-normal">{{ old('message') }}</textarea>
                    @error('message')<span class="text-xs font-normal text-destructive">{{ $message }}</span>@enderror
                </label>
                {{-- Honeypot and form-age token: bots fill the first and submit instantly. --}}
                <div aria-hidden="true" class="absolute -start-[9999px] h-0 w-0 overflow-hidden"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
                <input type="hidden" name="form_token" value="{{ $formToken }}">
                <button class="h-12 rounded-xl bg-primary font-bold text-primary-foreground">ارسال پیام</button>
            </form>
        @endif
    </section>
</x-site.layout>
