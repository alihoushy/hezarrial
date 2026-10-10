<?php

namespace App\Http\Controllers\Site;

use App\Content\Article;
use App\Content\ContentRepository;
use App\Content\Features;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /** Pages that exist as Blade views; the date is when their view file last changed. */
    private const PAGES = [
        '/' => 'home', '/features' => 'features', '/pricing' => 'pricing', '/download' => 'download', '/about' => 'about',
        '/security' => 'security', '/privacy' => 'privacy', '/terms' => 'terms', '/faq' => 'faq',
        '/tools/loan-calculator' => 'loan-calculator', '/contact' => 'contact',
    ];

    public function sitemap(ContentRepository $content): Response
    {
        $base = rtrim(config('app.url'), '/');
        $urls = [];

        foreach (self::PAGES as $path => $view) {
            $urls[] = [$base.($path === '/' ? '/' : $path), $this->modified(resource_path("views/site/{$view}.blade.php"))];
        }

        $featuresFile = $this->modified(app_path('Content/Features.php'));
        foreach (array_keys(Features::all()) as $slug) {
            $urls[] = [$base.'/features/'.$slug, $featuresFile];
        }

        $urls[] = [$base.'/changelog', $this->modified(base_path('CHANGELOG.md'))];

        foreach (ContentRepository::SECTIONS as $section) {
            $articles = $content->all($section);
            $urls[] = [$base.'/'.$section, $articles->map(fn (Article $a) => $a->updated ?? $a->date)->filter()->max()];

            foreach ($articles as $article) {
                $urls[] = [$base.$article->path(), $article->updated ?? $article->date];
            }
        }

        foreach ($content->categories('blog')->keys() as $category) {
            $urls[] = [$base.'/blog/category/'.rawurlencode($category), null];
        }

        return response()->view('site.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /app/',
            'Disallow: /api/',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password/',
            'Disallow: /email/',
            'Disallow: /user/',
            '',
            'Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml',
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function modified(string $file): ?CarbonImmutable
    {
        return is_file($file) ? CarbonImmutable::createFromTimestamp(filemtime($file)) : null;
    }
}
