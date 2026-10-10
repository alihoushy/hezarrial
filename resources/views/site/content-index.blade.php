@php
    $path = $category ? '/blog/category/'.rawurlencode($category) : '/'.$section;
    $crumbs = [['label' => 'خانه', 'url' => '/'], ['label' => ['blog' => 'مجله', 'help' => 'راهنما', 'learn' => 'آموزش'][$section], 'url' => '/'.$section]];
    if ($category) { $crumbs[] = ['label' => $category, 'url' => $path]; }
@endphp
<x-site.layout :title="$heading" :description="$lead" :path="$path" :breadcrumbs="$crumbs">
    <section class="mx-auto max-w-6xl px-4 pt-10">
        <h1 class="text-4xl leading-[1.4] font-extrabold">{{ $heading }}</h1>
        <p class="mt-4 max-w-2xl text-lg leading-9 text-muted-foreground">{{ $lead }}</p>

        @if ($section === 'blog' && $categories->isNotEmpty())
            <ul class="mt-8 flex flex-wrap gap-2" aria-label="دسته‌ها">
                <li><a href="/blog" class="inline-block rounded-full border px-4 py-2 text-sm {{ $category ? 'border-border bg-card hover:border-brand' : 'border-brand bg-brand/10 text-brand' }}">همه</a></li>
                @foreach ($categories as $name => $count)
                    <li><a href="/blog/category/{{ rawurlencode($name) }}" class="inline-block rounded-full border px-4 py-2 text-sm {{ $category === $name ? 'border-brand bg-brand/10 text-brand' : 'border-border bg-card hover:border-brand' }}">{{ $name }}</a></li>
                @endforeach
            </ul>
        @endif

        @if ($articles->isEmpty())
            <p class="mt-12 rounded-2xl border border-dashed border-border p-8 text-center text-muted-foreground">هنوز مطلبی منتشر نشده است. به‌زودی برمی‌گردیم.</p>
        @elseif ($section === 'help')
            <ul class="mt-10 divide-y divide-border rounded-2xl border border-border bg-card">
                @foreach ($articles as $article)
                    <li><a href="{{ $article->path() }}" class="block p-5 hover:bg-muted/50"><h2 class="font-bold">{{ $article->title }}</h2><p class="mt-1 text-sm leading-7 text-muted-foreground">{{ $article->description }}</p></a></li>
                @endforeach
            </ul>
        @else
            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)
                    <x-site.article-card :article="$article" />
                @endforeach
            </div>
        @endif
    </section>
    <x-site.cta />
</x-site.layout>
