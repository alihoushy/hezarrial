@php
    $qa = collect($groups)->flatten(1);
    $schema = [['@type' => 'FAQPage', 'mainEntity' => $qa->map(fn ($item) => ['@type' => 'Question', 'name' => $item[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item[1]]])->all()]];
@endphp
<x-site.layout
    title="سؤالات متداول"
    description="پاسخ پرسش‌های رایج درباره‌ی هزار ریال: رایگان بودن، امنیت اطلاعات، نصب روی گوشی، چک، تاریخ شمسی، حالت تاریک و خروجی داده‌ها."
    path="/faq"
    :schema="$schema"
    :breadcrumbs="[['label' => 'خانه', 'url' => '/'], ['label' => 'سؤالات متداول', 'url' => '/faq']]">
    <section class="mx-auto max-w-3xl px-4 pt-10">
        <h1 class="text-4xl leading-[1.4] font-extrabold">سؤالات متداول</h1>
        @foreach ($groups as $group => $items)
            <h2 class="mt-10 text-xl font-extrabold">{{ $group }}</h2>
            <div class="mt-4 divide-y divide-border rounded-2xl border border-border bg-card">
                @foreach ($items as [$question, $answer])
                    <details class="group p-5"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold [&::-webkit-details-marker]:hidden"><h3>{{ $question }}</h3><span class="text-xl text-muted-foreground transition-transform group-open:rotate-45" aria-hidden="true">+</span></summary><p class="mt-3 leading-8 text-muted-foreground">{{ $answer }}</p></details>
                @endforeach
            </div>
        @endforeach
        <p class="mt-10 text-center text-muted-foreground">جوابتان را پیدا نکردید؟ <a href="/contact" class="font-semibold text-brand underline underline-offset-4">برایمان بنویسید</a>.</p>
    </section>
</x-site.layout>
