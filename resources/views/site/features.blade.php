<x-site.layout
    title="ویژگی‌های هزار ریال"
    description="امکانات حسابداری شخصی هزار ریال: حساب‌ها و کارت‌ها، درآمد و هزینه، بدهی و طلب، چک، اقساط وام، بودجه‌بندی و گزارش‌های مالی."
    path="/features"
    :breadcrumbs="[['label' => 'خانه', 'url' => '/'], ['label' => 'ویژگی‌ها', 'url' => '/features']]">
    <section class="mx-auto max-w-6xl px-4 pt-10">
        <h1 class="text-4xl leading-[1.4] font-extrabold">ویژگی‌های هزار ریال</h1>
        <p class="mt-4 max-w-2xl text-lg leading-9 text-muted-foreground">این‌ها امکاناتی است که همین حالا کار می‌کند. هر کدام را باز کنید تا جزئیات و پرسش‌های رایجش را ببینید.</p>
        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($features as $slug => $feature)
                <a href="/features/{{ $slug }}" class="group rounded-2xl border border-border bg-card p-6 transition-shadow hover:shadow-md">
                    <span class="grid size-12 place-items-center rounded-xl bg-brand/10 text-brand"><x-site.icon :path="$feature['icon']" /></span>
                    <h2 class="mt-4 text-lg font-bold group-hover:text-brand">{{ $feature['title'] }}</h2>
                    <p class="mt-2 text-sm leading-7 text-muted-foreground">{{ $feature['summary'] }}</p>
                </a>
            @endforeach
        </div>

        <h2 id="planned" class="mt-16 text-2xl font-extrabold">به‌زودی</h2>
        <p class="mt-2 text-muted-foreground">این امکانات در برنامه‌ی کار است و هنوز در دسترس نیست.</p>
        <ul class="mt-6 grid gap-4 sm:grid-cols-2">
            @foreach ($planned as [$name, $text])
                <li class="rounded-2xl border border-dashed border-border p-5"><p class="font-bold">{{ $name }} <span class="ms-1 rounded-full bg-warning/15 px-2 py-0.5 text-xs font-semibold text-warning">به‌زودی</span></p><p class="mt-2 text-sm leading-7 text-muted-foreground">{{ $text }}</p></li>
            @endforeach
        </ul>
    </section>
    <x-site.cta />
</x-site.layout>
