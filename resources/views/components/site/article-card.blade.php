@props(['article'])
<article class="flex flex-col rounded-2xl border border-border bg-card p-5 transition-shadow hover:shadow-md">
    @if ($article->category)
        <a href="/blog/category/{{ rawurlencode($article->category) }}" class="mb-2 self-start rounded-full bg-brand/10 px-3 py-1 text-xs font-semibold text-brand">{{ $article->category }}</a>
    @endif
    <h3 class="text-lg leading-8 font-bold"><a href="{{ $article->path() }}" class="hover:text-brand">{{ $article->title }}</a></h3>
    <p class="mt-2 flex-1 text-sm leading-7 text-muted-foreground">{{ $article->description }}</p>
    <p class="mt-4 flex flex-wrap items-center gap-x-3 text-xs text-muted-foreground">
        @if ($article->date)<time datetime="{{ $article->date->toDateString() }}">{{ $article->persianDate() }}</time>@endif
        <span>{{ \App\Support\LoanCalculator::persianNumber($article->readingMinutes) }} دقیقه مطالعه</span>
    </p>
</article>
