<x-site.layout
    title="دانلود و نصب"
    description="هزار ریال را روی آیفون، اندروید و کامپیوتر نصب کنید: یک وب‌اپ (PWA) که مثل برنامه‌ی معمولی روی صفحه‌ی اصلی گوشی می‌نشیند و با همان حساب کار می‌کند."
    path="/download"
    :breadcrumbs="[['label' => 'خانه', 'url' => '/'], ['label' => 'دانلود و نصب', 'url' => '/download']]">
    <section class="mx-auto max-w-4xl px-4 pt-10">
        <h1 class="text-4xl leading-[1.4] font-extrabold">نصب هزار ریال روی گوشی و کامپیوتر</h1>
        <p class="mt-4 text-lg leading-9 text-muted-foreground">هزار ریال یک وب‌اپ است: بدون فروشگاه برنامه و بدون حجم زیاد، از مرورگر باز می‌شود و با چند لمس روی صفحه‌ی اصلی گوشی‌تان نصب می‌شود. روی همه‌ی دستگاه‌ها با یک حساب کار می‌کنید و داده‌ها همیشه هماهنگ‌اند.</p>

        {{-- Shown only on browsers that offer one-tap installation (Chrome, Edge, Android). --}}
        <div id="install-box" hidden class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-brand/40 bg-brand/5 p-5">
            <p class="font-semibold">این مرورگر می‌تواند هزار ریال را همین حالا نصب کند.</p>
            <button id="install-button" type="button" class="h-11 rounded-xl bg-primary px-6 font-bold text-primary-foreground">نصب هزار ریال</button>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-2">
            <section class="rounded-2xl border border-border bg-card p-6">
                <h2 class="text-xl font-extrabold">آیفون و آیپد (Safari)</h2>
                <ol class="mt-4 list-decimal space-y-3 ps-6 leading-8 text-muted-foreground">
                    <li>سایت را در <strong class="text-foreground">Safari</strong> باز کنید (نه مرورگر دیگر).</li>
                    <li>دکمه‌ی <strong class="text-foreground">اشتراک‌گذاری</strong> (مربع با فلش رو به بالا) را بزنید.</li>
                    <li>گزینه‌ی <strong class="text-foreground">Add to Home Screen</strong> (افزودن به صفحه‌ی اصلی) را انتخاب کنید.</li>
                    <li>نام را تأیید و <strong class="text-foreground">Add</strong> را بزنید.</li>
                </ol>
            </section>
            <section class="rounded-2xl border border-border bg-card p-6">
                <h2 class="text-xl font-extrabold">اندروید (Chrome)</h2>
                <ol class="mt-4 list-decimal space-y-3 ps-6 leading-8 text-muted-foreground">
                    <li>سایت را در <strong class="text-foreground">Chrome</strong> باز کنید.</li>
                    <li>منوی سه‌نقطه را بزنید.</li>
                    <li><strong class="text-foreground">Install app</strong> یا <strong class="text-foreground">Add to Home screen</strong> را انتخاب کنید.</li>
                    <li>نصب را تأیید کنید؛ آیکون هزار ریال روی صفحه‌ی اصلی می‌آید.</li>
                </ol>
            </section>
            <section class="rounded-2xl border border-border bg-card p-6">
                <h2 class="text-xl font-extrabold">کامپیوتر (Chrome و Edge)</h2>
                <p class="mt-4 leading-8 text-muted-foreground">در نوار آدرس روی آیکون نصب (مانیتور با فلش) کلیک کنید، یا از منوی مرورگر «Install Hezar Rial» را بزنید. برنامه در پنجره‌ی جدا و بدون نوار مرورگر باز می‌شود.</p>
            </section>
            <section class="rounded-2xl border border-dashed border-border p-6">
                <h2 class="text-xl font-extrabold">برنامه‌ی اندروید <span class="ms-1 rounded-full bg-warning/15 px-2 py-0.5 text-xs font-semibold text-warning">به‌زودی</span></h2>
                <p class="mt-4 leading-8 text-muted-foreground">برنامه‌ی اندروید برای ثبت خودکار پیامک بانکی در برنامه‌ی کار است. تا آن زمان، می‌توانید پیامک‌ها را با یک برنامه‌ی فوروارد پیامک یا میان‌بر آیفون به حسابتان بفرستید (راهنما در بخش راهنمای استفاده).</p>
            </section>
        </div>

        <div class="mt-10 text-center"><a href="/help" class="font-semibold text-brand underline underline-offset-4">راهنمای کامل استفاده</a></div>
    </section>
    <x-site.cta />
    <x-slot:head>
        <script nonce="{{ Vite::cspNonce() }}">
            let installPrompt;
            addEventListener('beforeinstallprompt', (event) => {
                event.preventDefault();
                installPrompt = event;
                document.getElementById('install-box').hidden = false;
            });
            addEventListener('DOMContentLoaded', () => {
                document.getElementById('install-button').addEventListener('click', async () => {
                    installPrompt.prompt();
                    await installPrompt.userChoice;
                    document.getElementById('install-box').hidden = true;
                });
            });
        </script>
    </x-slot:head>
</x-site.layout>
