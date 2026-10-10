@php
    $faq = collect($feature['faq'])->map(fn ($qa) => ['@type' => 'Question', 'name' => $qa[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]]])->all();
    $schema = [['@type' => 'WebPage', 'name' => $feature['title'], 'description' => $feature['description'], 'url' => config('app.url').'/features/'.$slug, 'isPartOf' => ['@id' => config('app.url').'/#website']]];
    if ($faq) {
        $schema[] = ['@type' => 'FAQPage', 'mainEntity' => $faq];
    }
@endphp
<x-site.layout
    :title="$feature['title']"
    :description="$feature['description']"
    :path="'/features/'.$slug"
    :schema="$schema"
    :breadcrumbs="[['label' => 'خانه', 'url' => '/'], ['label' => 'ویژگی‌ها', 'url' => '/features'], ['label' => $feature['title'], 'url' => '/features/'.$slug]]">
    <article class="mx-auto max-w-3xl px-4 pt-10">
        <span class="grid size-14 place-items-center rounded-2xl bg-brand/10 text-brand"><x-site.icon :path="$feature['icon']" class="size-7" /></span>
        <h1 class="mt-5 text-4xl leading-[1.4] font-extrabold">{{ $feature['headline'] }}</h1>
        <p class="mt-5 text-lg leading-9 text-muted-foreground">{{ $feature['intro'] }}</p>

        <div class="mt-10 grid gap-4 sm:grid-cols-2">
            @foreach ($feature['points'] as [$heading, $text])
                <section class="rounded-2xl border border-border bg-card p-5">
                    <h2 class="font-bold">{{ $heading }}</h2>
                    <p class="mt-2 text-sm leading-7 text-muted-foreground">{{ $text }}</p>
                </section>
            @endforeach
        </div>

        @if ($faq)
            <h2 class="mt-14 text-2xl font-extrabold">پرسش‌های رایج</h2>
            <div class="mt-5 divide-y divide-border rounded-2xl border border-border bg-card">
                @foreach ($feature['faq'] as [$question, $answer])
                    <div class="p-5"><h3 class="font-semibold">{{ $question }}</h3><p class="mt-2 leading-8 text-muted-foreground">{{ $answer }}</p></div>
                @endforeach
            </div>
        @endif

        <h2 class="mt-14 text-xl font-extrabold">ویژگی‌های دیگر</h2>
        <ul class="mt-4 flex flex-wrap gap-2">
            @foreach ($others as $otherSlug => $other)
                <li><a href="/features/{{ $otherSlug }}" class="inline-block rounded-full border border-border bg-card px-4 py-2 text-sm hover:border-brand hover:text-brand">{{ $other['title'] }}</a></li>
            @endforeach
        </ul>
    </article>
    <x-site.cta />
</x-site.layout>
