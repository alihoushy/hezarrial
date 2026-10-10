<?php

use App\Http\Controllers\Site\ContentController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\SitemapController;
use App\Content\Features;
use Illuminate\Support\Facades\Route;

/*
| The public website. Registered in the "site" middleware group (no session, no cookies); the
| contact form, which needs CSRF protection, lives in routes/web.php.
*/

Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/features', [PageController::class, 'features'])->name('features.index');
Route::get('/features/{slug}', [PageController::class, 'feature'])->whereIn('slug', array_keys(Features::all()))->name('features.show');

Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('/download', [PageController::class, 'download'])->name('download');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/security', [PageController::class, 'security'])->name('security');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/changelog', [PageController::class, 'changelog'])->name('changelog');
Route::get('/tools/loan-calculator', [PageController::class, 'loanCalculator'])->name('tools.loan-calculator');

Route::get('/blog', [ContentController::class, 'index'])->defaults('section', 'blog')->name('blog.index');
Route::get('/blog/feed.xml', [ContentController::class, 'feed'])->name('blog.feed');
Route::get('/blog/category/{category}', [ContentController::class, 'category'])->name('blog.category');
Route::get('/blog/{slug}', [ContentController::class, 'show'])->defaults('section', 'blog')->name('blog.show');

Route::get('/help', [ContentController::class, 'index'])->defaults('section', 'help')->name('help.index');
Route::get('/help/{slug}', [ContentController::class, 'show'])->defaults('section', 'help')->name('help.show');

Route::get('/learn', [ContentController::class, 'index'])->defaults('section', 'learn')->name('learn.index');
Route::get('/learn/{slug}', [ContentController::class, 'show'])->defaults('section', 'learn')->name('learn.show');

Route::view('/offline', 'site.offline')->name('offline');
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
