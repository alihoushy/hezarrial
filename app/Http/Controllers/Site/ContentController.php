<?php

namespace App\Http\Controllers\Site;

use App\Content\ContentRepository;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Blog, help pages and tutorials, all of them Markdown files in content/. */
class ContentController extends Controller
{
    private const TITLES = [
        'blog' => ['مجله‌ی هزار ریال', 'مقاله‌هایی درباره‌ی حسابداری شخصی، بودجه‌بندی، چک، وام و مدیریت پول؛ ساده و کاربردی.'],
        'help' => ['راهنمای استفاده', 'یادگیری گام‌به‌گام هزار ریال: شروع کار، ثبت تراکنش، چک، وام، اتصال پیامک بانکی و نصب برنامه روی گوشی.'],
        'learn' => ['آموزش‌ها', 'آموزش‌های مالی شخصی: مقاله و ویدئو برای مدیریت بهتر پول.'],
    ];

    public function __construct(private readonly ContentRepository $content) {}

    public function index(string $section): View
    {
        [$title, $description] = self::TITLES[$section];

        return view('site.content-index', [
            'section' => $section,
            'heading' => $title,
            'lead' => $description,
            'articles' => $this->content->all($section),
            'categories' => $this->content->categories($section),
            'category' => null,
        ]);
    }

    public function category(string $category): View
    {
        $articles = $this->content->all('blog')->where('category', $category)->values();
        abort_if($articles->isEmpty(), 404);

        return view('site.content-index', [
            'section' => 'blog',
            'heading' => 'مجله: '.$category,
            'lead' => 'مقاله‌های مجله‌ی هزار ریال در دسته‌ی «'.$category.'».',
            'articles' => $articles,
            'categories' => $this->content->categories('blog'),
            'category' => $category,
        ]);
    }

    // Parameters are filled by position: {slug} comes from the path, then the "section" default.
    public function show(Request $request, string $slug, string $section): View
    {
        $article = $this->content->find($section, $slug);
        abort_unless($article, 404);

        // A tutorial with a video may embed it from Aparat and nowhere else.
        if ($article->video) {
            $request->attributes->set('csp.frame-src', ['https://www.aparat.com']);
        }

        return view('site.content-show', ['article' => $article, 'related' => $this->content->related($article)]);
    }

    public function feed(): Response
    {
        return response()
            ->view('site.feed', ['articles' => $this->content->all('blog')->take(20)])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
