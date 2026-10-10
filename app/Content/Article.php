<?php

namespace App\Content;

use Carbon\CarbonImmutable;
use App\Support\Digits;
use Morilog\Jalali\Jalalian;

/** One Markdown page (blog post, help page or tutorial), already rendered. */
final class Article
{
    /**
     * @param  list<array{id: string, text: string, level: int}>  $toc
     * @param  list<string>  $tags
     */
    public function __construct(
        public readonly string $section,
        public readonly string $slug,
        public readonly string $title,
        public readonly string $description,
        public readonly ?CarbonImmutable $date,
        public readonly ?CarbonImmutable $updated,
        public readonly ?string $category,
        public readonly ?string $cover,
        public readonly ?string $video,
        public readonly array $tags,
        public readonly string $html,
        public readonly array $toc,
        public readonly int $readingMinutes,
        public readonly int $order,
    ) {}

    /** Plain data, so the cache never has to serialize an object (Laravel refuses unknown classes). */
    public function toArray(): array
    {
        return [
            'section' => $this->section, 'slug' => $this->slug, 'title' => $this->title, 'description' => $this->description,
            'date' => $this->date?->toIso8601String(), 'updated' => $this->updated?->toIso8601String(),
            'category' => $this->category, 'cover' => $this->cover, 'video' => $this->video, 'tags' => $this->tags,
            'html' => $this->html, 'toc' => $this->toc, 'readingMinutes' => $this->readingMinutes, 'order' => $this->order,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            section: $data['section'], slug: $data['slug'], title: $data['title'], description: $data['description'],
            date: $data['date'] ? CarbonImmutable::parse($data['date']) : null,
            updated: $data['updated'] ? CarbonImmutable::parse($data['updated']) : null,
            category: $data['category'], cover: $data['cover'], video: $data['video'], tags: $data['tags'],
            html: $data['html'], toc: $data['toc'], readingMinutes: $data['readingMinutes'], order: $data['order'],
        );
    }

    public function path(): string
    {
        return '/'.$this->section.'/'.$this->slug;
    }

    /** "۱۸ مهر ۱۴۰۵": the date shown to readers. */
    public function persianDate(?CarbonImmutable $date = null): ?string
    {
        $date ??= $this->date;

        return $date ? Digits::persian(Jalalian::fromCarbon($date->toMutable())->format('j F Y')) : null;
    }
}
