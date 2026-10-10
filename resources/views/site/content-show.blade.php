@php
    $base = rtrim(config('app.url'), '/');
    $sectionLabel = ['blog' => 'مجله', 'help' => 'راهنما', 'learn' => 'آموزش'][$article->section];
    $schema = [[
        '@type' => $article->section === 'help' ? 'TechArticle' : 'BlogPosting',
        'headline' => $article->title,
        'description' => $article->description,
        'inLanguage' => 'fa-IR',
        'mainEntityOfPage' => $base.$article->path(),
        'datePublished' => $article->date?->toIso8601String(),
        'dateModified' => ($article->updated ?? $article->date)?->toIso8601String(),
        'image' => $article->cover ? $base.$article->cover : $base.'/images/og-default.png',
        'author' => ['@id' => $base.'/#organization'],
        'publisher' => ['@id' => $base.'/#organization'],
    ]];
    if ($article->video) {
        $schema[] = ['@type' => 'VideoObject', 'name' => $article->title, 'description' => $article->description, 'embedUrl' => 'https://www.aparat.com/video/video/embed/videohash/'.$article->video.'/vt/frame', 'uploadDate' => $article->date?->toIso8601String(), 'thumbnailUrl' => $article->cover ? $base.$article->cover : $base.'/images/og-default.png'];
    }
@endphp
<x-site.layout
    :title="$article->title"
    :description="$article->description"
    :path="$article->path()"
    :image="$article->cover"
    :type="$article->section === 'help' ? 'website' : 'article'"
    :published="$article->date?->toIso8601String()"
    :modified="($article->updated ?? $article->date)?->toIso8601String()"
    :schema="$schema"
    :breadcrumbs="[['label' => 'خانه', 'url' => '/'], ['label' => $sectionLabel, 'url' => '/'.$article->section], ['label' => $article->title, 'url' => $article->path()]]">
    <article class="mx-auto max-w-3xl px-4 pt-10">
        @if ($article->category)
            <a href="/blog/category/{{ rawurlencode($article->category) }}" class="mb-3 inline-block rounded-full bg-brand/10 px-3 py-1 text-sm font-semibold text-brand">{{ $article->category }}</a>
        @endif
        <h1 class="text-4xl leading-[1.45] font-extrabold">{{ $article->title }}</h1>
        <p class="mt-4 text-lg leading-9 text-muted-foreground">{{ $article->description }}</p>
        {{-- Separate spans: dots between digits would otherwise be reordered by the bidi algorithm. --}}
        <p class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
            @if ($article->date)<span>انتشار: <time datetime="{{ $article->date->toDateString() }}">{{ $article->persianDate() }}</time></span>@endif
            @if ($article->updated)<span>به‌روزرسانی: <time datetime="{{ $article->updated->toDateString() }}">{{ $article->persianDate($article->updated) }}</time></span>@endif
            <span>{{ \App\Support\LoanCalculator::persianNumber($article->readingMinutes) }} دقیقه مطالعه</span>
        </p>

        @if ($article->video)
            <div class="mt-8 aspect-video overflow-hidden rounded-2xl border border-border bg-muted">
                <iframe src="https://www.aparat.com/video/video/embed/videohash/{{ $article->video }}/vt/frame" title="{{ $article->title }}" loading="lazy" allowfullscreen class="size-full"></iframe>
            </div>
        @endif

        @if (count($article->toc) > 2)
            <nav class="mt-8 rounded-2xl border border-border bg-card p-5" aria-label="فهرست مطالب">
                <p class="mb-2 font-bold">در این مطلب</p>
                <ol class="space-y-1.5 text-sm">
                    @foreach ($article->toc as $item)
                        <li class="{{ $item['level'] === 3 ? 'ps-4' : '' }}"><a href="#{{ $item['id'] }}" class="text-muted-foreground hover:text-brand">{{ $item['text'] }}</a></li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <div class="prose-site mt-8">{!! $article->html !!}</div>
    </article>

    @if ($related->isNotEmpty())
        <section class="mx-auto mt-16 max-w-6xl px-4">
            <h2 class="text-2xl font-extrabold">بیشتر بخوانید</h2>
            <div class="mt-6 grid gap-5 md:grid-cols-3">
                @foreach ($related as $item)
                    <x-site.article-card :article="$item" />
                @endforeach
            </div>
        </section>
    @endif
    <x-site.cta />
</x-site.layout>
