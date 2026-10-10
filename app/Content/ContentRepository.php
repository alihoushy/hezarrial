<?php

namespace App\Content;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Reads content/{section}/*.md (a YAML front matter block, then Markdown). Rendered pages are
 * cached until a file changes, so a request normally never touches the Markdown parser.
 */
class ContentRepository
{
    public const SECTIONS = ['blog', 'help', 'learn'];

    private ?MarkdownConverter $converter = null;

    /** @return Collection<int, Article> newest first (blog, learn) or by the "order" front matter (help) */
    public function all(string $section): Collection
    {
        $this->assertSection($section);

        $files = File::glob(base_path("content/{$section}/*.md")) ?: [];
        $version = md5(implode('|', array_map(fn ($file) => $file.filemtime($file), $files)));

        /** @var list<array<string, mixed>> $rows */
        $rows = Cache::rememberForever("content.{$section}.{$version}", fn () => array_map(fn ($file) => $this->parse($section, $file)->toArray(), $files));

        return collect($rows)->map(Article::fromArray(...))->sort(function (Article $a, Article $b) use ($section) {
            return $section === 'help'
                ? [$a->order, $a->title] <=> [$b->order, $b->title]
                : [$b->date?->timestamp, $a->slug] <=> [$a->date?->timestamp, $b->slug];
        })->values();
    }

    public function find(string $section, string $slug): ?Article
    {
        return $this->all($section)->firstWhere('slug', $slug);
    }

    /** @return Collection<string, int> category => number of articles */
    public function categories(string $section): Collection
    {
        return $this->all($section)->whereNotNull('category')->countBy('category')->sortKeys();
    }

    /** Other pages of the same category first, then the newest ones. */
    public function related(Article $article, int $limit = 3): Collection
    {
        return $this->all($article->section)
            ->reject(fn (Article $other) => $other->slug === $article->slug)
            ->sortByDesc(fn (Article $other) => (int) ($other->category && $other->category === $article->category))
            ->take($limit)
            ->values();
    }

    /**
     * A standalone Markdown file (CHANGELOG.md), rendered and cached until it changes.
     *
     * @return array{html: string, toc: list<array{id: string, text: string, level: int}>}
     */
    public function renderFile(string $path): array
    {
        abort_unless(is_file($path), 404);

        return Cache::rememberForever('content.file.'.md5($path.filemtime($path)), function () use ($path) {
            [$html, $toc] = $this->withHeadingIds($this->converter()->convert((string) file_get_contents($path))->getContent());

            return ['html' => $html, 'toc' => $toc];
        });
    }

    private function assertSection(string $section): void
    {
        abort_unless(in_array($section, self::SECTIONS, true), 404);
    }

    private function parse(string $section, string $file): Article
    {
        $result = $this->converter()->convert((string) file_get_contents($file));
        $meta = $result instanceof RenderedContentWithFrontMatter ? (array) $result->getFrontMatter() : [];
        [$html, $toc] = $this->withHeadingIds($result->getContent());

        $title = (string) ($meta['title'] ?? Str::headline(basename($file, '.md')));

        return new Article(
            section: $section,
            slug: basename($file, '.md'),
            title: $title,
            description: (string) ($meta['description'] ?? Str::limit(strip_tags($html), 150)),
            date: $this->date($meta['date'] ?? null),
            updated: $this->date($meta['updated'] ?? null),
            category: isset($meta['category']) ? (string) $meta['category'] : null,
            cover: isset($meta['cover']) ? (string) $meta['cover'] : null,
            video: isset($meta['video']) ? (string) $meta['video'] : null,
            tags: array_map('strval', (array) ($meta['tags'] ?? [])),
            html: $html,
            toc: $toc,
            readingMinutes: max(1, (int) ceil(count(preg_split('/\s+/u', trim(strip_tags($html)), -1, PREG_SPLIT_NO_EMPTY) ?: []) / 180)),
            order: (int) ($meta['order'] ?? 100),
        );
    }

    private function converter(): MarkdownConverter
    {
        if (! $this->converter) {
            $environment = new Environment([
                // Content is written by us, but nothing raw or scripted may slip into a page.
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
            $environment->addExtension(new CommonMarkCoreExtension);
            $environment->addExtension(new GithubFlavoredMarkdownExtension);
            $environment->addExtension(new FrontMatterExtension);

            $this->converter = new MarkdownConverter($environment);
        }

        return $this->converter;
    }

    /**
     * Gives every h2/h3 an id (anchor links and the table of contents) and lists them.
     *
     * @return array{string, list<array{id: string, text: string, level: int}>}
     */
    private function withHeadingIds(string $html): array
    {
        $toc = [];
        $used = [];

        $html = preg_replace_callback('#<h([23])>(.*?)</h\1>#su', function (array $m) use (&$toc, &$used) {
            $text = trim(strip_tags($m[2]));
            // Anchors keep Persian letters (readable in the address bar) instead of transliterating them.
            $id = trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($text)) ?? '', '-') ?: 'section';
            $id = isset($used[$id]) ? $id.'-'.(++$used[$id]) : tap($id, fn ($id) => $used[$id] = 1);
            $toc[] = ['id' => $id, 'text' => html_entity_decode($text), 'level' => (int) $m[1]];

            return '<h'.$m[1].' id="'.$id.'">'.$m[2].'</h'.$m[1].'>';
        }, $html) ?? $html;

        return [$html, $toc];
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_int($value) ? CarbonImmutable::createFromTimestampUTC($value) : CarbonImmutable::parse((string) $value);
    }
}
