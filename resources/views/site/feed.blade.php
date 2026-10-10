<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>{{ config('app.name') }} — مجله</title>
    <link>{{ rtrim(config('app.url'), '/') }}/blog</link>
    <description>مقاله‌هایی درباره‌ی حسابداری شخصی و مدیریت پول</description>
    <language>fa-IR</language>
    <atom:link href="{{ rtrim(config('app.url'), '/') }}/blog/feed.xml" rel="self" type="application/rss+xml"/>
    @foreach ($articles as $article)
    <item>
        <title>{{ $article->title }}</title>
        <link>{{ rtrim(config('app.url'), '/') }}{{ $article->path() }}</link>
        <guid isPermaLink="true">{{ rtrim(config('app.url'), '/') }}{{ $article->path() }}</guid>
        @if ($article->date)<pubDate>{{ $article->date->toRfc2822String() }}</pubDate>@endif
        <description>{{ $article->description }}</description>
    </item>
    @endforeach
</channel>
</rss>
